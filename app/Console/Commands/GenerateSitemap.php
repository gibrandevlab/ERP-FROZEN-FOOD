<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate'; // Ini nama panggilannya nanti
    protected $description = 'Bikin sitemap otomatis buat SEO';

    public function handle()
    {
        SitemapGenerator::create(config('app.url'))
            ->writeToFile(public_path('sitemap.xml'));

        $this->info('Mantap! sitemap.xml sudah muncul di folder public.');
    }
}
