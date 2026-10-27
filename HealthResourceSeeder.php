<?php
namespace Database\Seeders;
use App\Models\HealthResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
class HealthResourceSeeder extends Seeder {
 public function run(): void {
    foreach(json_decode(file_get_contents(__DIR__.'/resources.json'),true,512,JSON_THROW_ON_ERROR) as $item){HealthResource::updateOrCreate(['category'=>$item['category'],'url'=>$item['url']],$item);}
    Cache::forget('aura.resource.categories');
 }
}
