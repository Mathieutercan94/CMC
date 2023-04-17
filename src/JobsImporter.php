<?php

class JobsImporter
{
    private PDO $db;

    private string $file;
    private string $path;
    public function __construct(string $host, string $username, string $password, string $databaseName, string $path)
    {
       $this->path = $path;
       $this->files = [];
       $this->files = $this->getFiles();
        try {
            $this->db = new PDO('mysql:host=' . $host . ';dbname=' . $databaseName, $username, $password);
        } catch (Exception $e) {
            die('DB error: ' . $e->getMessage() . "\n");
        }
    }
   private function getFiles(): array
   {
       $files = scandir($this->path);
       $validFiles = array_filter($files, function ($file) {
           return is_file($this->path . $file);
       });
       return $validFiles;
   }

    private function parseXml(string $file): int
    {
        $xml = simplexml_load_file(RESSOURCES_DIR . $file);
        $count = 0;
        foreach ($xml->item as $item) {
            $this->insertJob($item->ref, $item->title, $item->description, $item->url, $item->company, $item->pubDate);
            $count++;
        }
        return $count;
    }

    private function parseJson(string $file): int
     {
       $json = file_get_contents(RESSOURCES_DIR . $file);
       $tableau = json_decode($json, true);
       $count = 0;
       foreach ($tableau['item'] as $item) {
            $this->insertJob($item['ref'], $item['title'], $item['description'], $item['url'], $item['company'], date('Y/m/d', strtotime($item['pubDate'])));
            $count++;
           }
       return $count;
     }

    private function insertJob(string $ref, string $title, string $description, string $url, string $company, string $pubDate): void
    {
        $model = $this->db->prepare('INSERT INTO job (reference, title, description, url, company_name, publication) VALUES (?, ?, ?, ?, ?, ?)');
        $model->execute([$ref, $title, $description, $url, $company, $pubDate]);
    }

    public function importJobs(): int
    {
        $this->db->exec('DELETE FROM job');
        $count = 0;

        foreach($this->files as $file) {
            $info = pathinfo($file);
            if ($info['extension'] === "xml") {
                $count = $count + $this->parseXml($file);
            } else if ($info['extension'] === "json") {
                $count = $count + $this->parseJson($file);
            }
        }

        return $count;
    }
}
