<?php
require_once 'Database.php';

class SuskiSpider
{
    public  $dataList = [];


    public function getCh($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $ch;
    }

    public function startRequests()
    {
        $url = 'https://susicky.heureka.cz';
        $ch = $this->getCh($url);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            curl_close($ch);
            $pos = stripos($response, "DPY 8506 GXB2");
            $this->parse($response, $url, 0);
        }
    }

    public function parse($response, $url)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($response);
        $xpath = new DOMXPath($doc);
        $productLinks = $xpath->query("//h3[@class='c-product__title']/a[@class='c-product__link']/@href");
        $count = 0;
        foreach ($productLinks as $link) {
            $href = $link->nodeValue;
            $ch = $this->getCh($href);
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            $count += 1;

            if ($response === false) {
                echo "cURL Error: " . curl_error($ch);
            } else {
                curl_close($ch);
                $this->getProduct($response, $href, 0);
            }
            if ($count === 2){

                break;
            }
        }
    
    }
    public function getProduct($response, $href)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($response);
        $xpath = new DOMXPath($doc);
        $dataDict = [];
        $dataDict['productName'] = trim($xpath->query("//div[@class='c-top-position__right-col']//h1/text()")->item(0)->nodeValue);
        $dataDict['rating'] = trim($xpath->query("//span[text() = ' %']/preceding-sibling::span/following-sibling::span/text()")->item(0)->nodeValue);
        $listeElements = $xpath->query("//ul[@class='c-box-list c-box-list--reviews o-wrapper__overflowing@lteLine']/li");
        
        $dataDict['all_reviewer'] = [];
        foreach ($listeElements as $listelemt) {
            $variant = [];
            $paragraphs = $xpath->query("./div[1]/div[1]/p[1]", $listelemt);

            $variant['revievername'] = '';
            
            foreach ($paragraphs as $paragraph) {
                $variant['revievername'] .= trim($paragraph->nodeValue) . ' ';
            }    
            $paragraphs1 = $xpath->query("./div[1]/div[1]/p[2]/time", $listelemt);

            $variant['reviever_message'] = '';
            
            foreach ($paragraphs1 as $paragraph) {
                $variant['reviever_message'] .= trim($paragraph->nodeValue) . ' ';
    
            }              
            $dataDict['all_reviewer'][] = $variant;
        }
        $this->dataList[] = $dataDict;
        $Elements = $xpath->query("//ul[@class='thumbs u-column']/li/img/@src");
        foreach ($Elements as $links) {
            $imageUrl = $links->nodeValue;
            $imagename = "";
            $imagename = str_replace(['/', '-', ":", ".", ".jpg"], '', $imageUrl);
            echo $imagename, "\n";
            $productName = $dataDict['productName'];
            $this->imageresponse($imageUrl, $imagename, $productName, 0);
        }
    }

    public function imageresponse($imageUrl, $imagename, $productName)
    {
        $imageData = file_get_contents($imageUrl);
        echo $imagename, ">>>>>>>>>>>>>>>>>>>", "\n";
        $imageFilename = "imageOutput/{$imagename}.jpg";
        if (!file_exists(dirname($imageFilename))) {
            mkdir(dirname($imageFilename), 0755, true);
        }
        file_put_contents($imageFilename, $imageData);
    }
    public function saveDataToFile()
    {
        $jsonEncodedData = json_encode($this->dataList, JSON_UNESCAPED_UNICODE);

        file_put_contents("susky.json", $jsonEncodedData);
        
        $jsonString = file_get_contents('susky.json');
        $data = json_decode($jsonString, true);
        $servername = "localhost:3306";
        $username = "root";
        $password = "";
        $database = "hemant";
        $databaseHandler = new DatabaseHandler($servername, $username, $password, $database);
        $databaseHandler->createTable();
        $databaseHandler->insertData($data);
        $databaseHandler->closeConnection();
    }
}