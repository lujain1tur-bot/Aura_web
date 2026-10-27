<?php
namespace Database\Seeders;
use App\Models\Quote;
use Illuminate\Database\Seeder;
class QuoteSeeder extends Seeder {
 public function run(): void {
    $items=json_decode(file_get_contents(__DIR__.'/quotes.json'),true,512,JSON_THROW_ON_ERROR);
    foreach($items as $item){
        $legacy=$item['legacy_content'];unset($item['legacy_content']);
        Quote::whereNull('user_id')->where('content',$legacy)->update($item);
        Quote::firstOrCreate(['content'=>$item['content']],$item);
    }
 }
}
