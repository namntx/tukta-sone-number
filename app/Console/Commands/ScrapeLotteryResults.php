<?php

namespace App\Console\Commands;

use App\Services\LotteryResultScraper;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScrapeLotteryResults extends Command
{
    protected $signature = 'kqxs:scrape {date?} {--region=nam}';
    protected $description = 'Scrape kết quả xổ số theo ngày/miền từ AZ24';

    public function handle(LotteryResultScraper $scraper)
    {
        $dateArg = $this->argument('date') ?: now()->toDateString();
        $date    = Carbon::parse($dateArg);
        $region  = strtolower($this->option('region') ?: 'nam');

        $this->info("Scraping $dateArg region=$region ...");
        $rows = $scraper->scrapeDaily($date, $region);

        $this->info("Saved results: ".count($rows));
        return self::SUCCESS;
    }
}
