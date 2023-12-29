<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

final class FetchTrees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-trees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Fetching SJC trees");

        //when you have time, move this to another place
        $collection = (new \MongoDB\Client('mongodb://localhost:27017'))->localprojects->trees;

        $client = new Client();
        $totalTrees = 50041;

        $progressBar = $this->output->createProgressBar($totalTrees);
        $progressBar->start();

        $trees = [];

        for ($i = 40000 ; $i <= $totalTrees; $i++ ) {

            $url = "https://arvores.sjc.sp.gov.br/$i";

            try {
                $response = $client->get($url);
            } catch (\Exception $e) {
                $this->error('it was not possible to fetch the tree: ' . $i);
                continue;
            }

            $html = $response->getBody()->getContents();

            foreach (explode(PHP_EOL, $html) as $number => $line) {

                if (str_contains($line, "Nome Popular")) {
                    preg_match('/<strong>(.*?)<\/strong>/', $line, $matches);
                    $treeName = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                }

                if (str_contains($line, "Latitude")) {

                    list($latitudePortion, $longitudePortion) = explode(" / ", $line);

                    preg_match('/<strong>(.*?)<\/strong>/', $latitudePortion, $matches);

                    $latitude = $matches[1];

                    preg_match('/<strong>(.*?)<\/strong>/', $longitudePortion, $matches);

                    $longitude = $matches[1];
                }
            }


            $trees[] = [
                'id' => $i, 'name' =>  $treeName, 'latitude' => $latitude, 'longitude' => $longitude
            ];

            if (count($trees) === 50 || $i == $totalTrees) {
                $collection->insertMany($trees);
                $trees = [];
            }

            /*
            file_put_contents(
                '/Users/brunoborgesdasilva/Projects/sjc-fruits/resources/trees.csv',
                "$i,$treeName,$latitude,$longitude" . PHP_EOL,
                FILE_APPEND
            ); */
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
//query in MongoDb
//{ $or: [{"name" : {$regex : "Abacate"}}, {"name" : {$regex : "Pitanga"}}, {"name" : {$regex : "Limoeiro"}},{"name" : {$regex : "Goiaba"}},{"name" : {$regex : "Manga"}},{"name" : {$regex : "Amora"}},{"name" : {$regex : "Jabuticaba"}}, {"name" : {$regex : "Fruta-do-conde"}}, {"name" : {$regex : "Acerola"}},{"name" : {$regex : "Laranja"}}, {"name" : {$regex : "Jaqueira"}}]}
