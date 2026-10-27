<?php
// Repeatable integration checks with a separate in-memory SQLite database.
// No accounts or content in the application's database are changed.
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app['config']->set('database.default', 'sqlite');
$app['config']->set('database.connections.sqlite.database', ':memory:');
$app['config']->set('cache.default', 'array');
Illuminate\Support\Facades\DB::purge('sqlite');
$checks = 0;
function check(string $label, bool $passed): void {
    global $checks;
    if (!$passed) { throw new RuntimeException($label); }
    $checks++;
    echo "PASS {$label}\n";
}
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Quote;

check('Fresh migrations complete', Artisan::call('migrate', ['--force' => true]) === 0);
Artisan::call('db:seed', ['--force' => true]);
check('102 seeded quotes', Quote::count() === 102);
Artisan::call('db:seed', ['--force' => true]);
check('Seeding is idempotent', Quote::count() === 102);
$user = User::create(['name'=>'Test user', 'email'=>'schema@example.test', 'password'=>'TestPassword123!']);
check('Password hash verifies', Hash::check('TestPassword123!', $user->password) && $user->password !== 'TestPassword123!');
$quote = $user->quotes()->create(['content'=>'Test message', 'category'=>'Mental wellbeing', 'source_type'=>'user', 'is_verified'=>false]);
check('Quote belongs to user', $quote->user->id === $user->id);
$user->likedQuotes()->attach($quote->id);
$user->savedQuotes()->attach($quote->id);
check('Like and save relationships persist', $quote->likes()->count() === 1 && $quote->savers()->count() === 1);
$duplicateBlocked = false;
try { $user->likedQuotes()->attach($quote->id); } catch (Illuminate\Database\QueryException $e) { $duplicateBlocked = true; }
check('Unique interaction constraint', $duplicateBlocked);
$quote->delete();
check('Quote deletion cascades likes and saves', DB::table('quote_likes')->count() === 0 && DB::table('quote_saves')->count() === 0);
$kept = $user->quotes()->create(['content'=>'The content remains after account deletion', 'category'=>'General']);
$user->delete();
check('User deletion preserves quote and clears owner', $kept->fresh() !== null && $kept->fresh()->user_id === null);
check('All 17 categories are shared', count(Quote::CATEGORIES) === 17 && in_array('Asthma', Quote::CATEGORIES, true));
foreach (Quote::CATEGORIES as $category) {
    check('Valid category '. $category, Validator::make(['category'=>$category], ['category'=>['required', Rule::in(Quote::CATEGORIES)]])->passes());
}
check('Foreign keys remain consistent', count(DB::select('PRAGMA foreign_key_check')) === 0);

check('13 official health resources', \App\Models\HealthResource::count() === 13);
check('Official resource URL allowlist', \App\Models\HealthResource::all()->every(fn($r) => $r->safeUrl() !== null));
$badResource = new \App\Models\HealthResource(['url'=>'javascript:alert(1)']);
check('Unsafe resource URL rejected', $badResource->safeUrl() === null);
$owner = User::create(['name'=>'Collection owner','email'=>'owner@example.test','password'=>'Password123!']);
$sample = Quote::first();
$owner->interests()->create(['category'=>'Sleep']);
check('Interests belong to user', $owner->interests()->count() === 1);
$collection = $owner->collections()->create(['name'=>'Quiet moments']);
$owner->savedQuotes()->attach($sample->id);
$collection->quotes()->syncWithoutDetaching([$sample->id]);
$collection->quotes()->syncWithoutDetaching([$sample->id]);
check('Collection membership is unique', $collection->quotes()->count() === 1);
$owner->reports()->create(['quote_id'=>$sample->id,'reason'=>'Other','status'=>'open']);
check('Report has owning account', $owner->reports()->count() === 1);
$collection->delete();
check('Deleting collection keeps saved quote', $owner->savedQuotes()->count() === 1 && DB::table('collection_items')->count() === 0);
$owner->delete();
check('User deletion clears private interests and collections', DB::table('user_interests')->count() === 0 && DB::table('collections')->count() === 0);
check('Report submitter becomes NULL on user deletion', DB::table('quote_reports')->whereNull('user_id')->count() === 1);
check('Final foreign key integrity', count(DB::select('PRAGMA foreign_key_check')) === 0);
echo "\nAll {$checks} integration checks passed. The application database was not modified.\n";
