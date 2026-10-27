<?php
// Run only against an isolated PostgreSQL test database.
declare(strict_types=1);
if (getenv('APP_ENV') !== 'testing') { fwrite(STDERR, "APP_ENV=testing is required.\n"); exit(1); }
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../docker/runtime-key.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Quote;
use App\Models\HealthResource;
$checks=0;
function expect(string $label, bool $passed): void {
 global $checks;
 if (!$passed) { throw new RuntimeException($label); }
 echo "PASS {$label}\n"; $checks++;
}
expect('PostgreSQL connection', DB::connection()->getDriverName() === 'pgsql');
expect('17 application tables', count(DB::select("select tablename from pg_tables where schemaname='public'")) === 17);
expect('102 seeded quotes', Quote::count() === 102);
expect('13 official health resources', HealthResource::count() === 13);
Artisan::call('db:seed',['--force'=>true]);
expect('Seeders are repeatable', Quote::count() === 102 && HealthResource::count() === 13);
expect('Official resource links', HealthResource::all()->every(fn($r)=>$r->safeUrl() !== null));
$seed=str_repeat('s',48);$key=deploymentKey('', $seed);
expect('Stable deployment key across restarts', deploymentKey('', $seed) === $key);
expect('Laravel encryption key is 32 bytes', strlen(base64_decode(substr($key,7),true)) === 32);
expect('Existing APP_KEY is preserved', deploymentKey($key,'') === $key);
$rejected=false;
try { deploymentKey('','short'); } catch (RuntimeException $e) { $rejected=true; }
expect('Missing or weak key seed rejected', $rejected);
DB::beginTransaction();
try {
 $user=User::create(['name'=>'Deployment check','email'=>'deployment-'.bin2hex(random_bytes(6)).'@example.test','password'=>'TestOnlyPassword123!']);
 expect('Password securely hashed', Hash::check('TestOnlyPassword123!',$user->password));
 $quote=$user->quotes()->create(['content'=>'A PostgreSQL deployment check.','category'=>'Sleep','source_type'=>'user','is_verified'=>false]);
 expect('Quote owner relationship', $quote->user->id === $user->id);
 $user->likedQuotes()->attach($quote->id);
 $user->savedQuotes()->attach($quote->id);
 expect('Like and save persist', $quote->likes()->count()===1 && $quote->savers()->count()===1);
 $collection=$user->collections()->create(['name'=>'Deployment checks']);
 $collection->quotes()->syncWithoutDetaching([$quote->id]);
 $collection->quotes()->syncWithoutDetaching([$quote->id]);
 expect('Unique collection membership', $collection->quotes()->count()===1);
 $user->interests()->create(['category'=>'Sleep']);
 expect('Interests persist', $user->interests()->count()===1);
 $user->reports()->create(['quote_id'=>$quote->id,'reason'=>'Other','status'=>'open']);
 expect('Reports persist', $user->reports()->count()===1);
 $quote->delete();
 expect('Quote deletion cascades interactions', DB::table('quote_likes')->where('quote_id',$quote->id)->count()===0 && DB::table('quote_saves')->where('quote_id',$quote->id)->count()===0);
 expect('Quote deletion cascades collection membership and reports', $collection->quotes()->count()===0 && $user->reports()->count()===0);
 $kept=$user->quotes()->create(['content'=>'A retained quote.','category'=>'General']);
 $user->reports()->create(['quote_id'=>$kept->id,'reason'=>'Other','status'=>'open']);
 $user->delete();
 expect('Account deletion clears private data', DB::table('collections')->where('user_id',$user->id)->count()===0 && DB::table('user_interests')->where('user_id',$user->id)->count()===0);
 expect('Quote retained without deleted owner', $kept->fresh()->user_id === null);
 expect('Report retained without deleted submitter', DB::table('quote_reports')->where('quote_id',$kept->id)->whereNull('user_id')->count()===1);
} finally { DB::rollBack(); }
echo "All {$checks} PostgreSQL deployment checks passed.\n";
