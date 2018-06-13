<?php

namespace Keboola\DarkSkyAugmentation\Tests;

use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class FunctionalTest extends TestCase
{

    public function testFunctional()
    {
        $temp = new Temp();
        $temp->initRunFolder();

        file_put_contents($temp->getTmpFolder() . '/config.json', json_encode([
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-main.coordinates',
                            'destination' => 'coordinates.csv'
                        ]
                    ]
                ],
            ],
            'parameters' => [
                '#apiToken' => DARKSKY_KEY,
                'conditions' => ['windSpeed']
            ]
        ]));

        mkdir($temp->getTmpFolder().'/in');
        mkdir($temp->getTmpFolder().'/in/tables');
        copy(__DIR__ . '/data/data.csv', $temp->getTmpFolder().'/in/tables/coordinates.csv');
        copy(__DIR__ . '/data/data.csv.manifest', $temp->getTmpFolder().'/in/tables/coordinates.csv.manifest');

        $process = new Process("php ".__DIR__."/../../src/run.php --data=".$temp->getTmpFolder());
        $process->setTimeout(null);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->fail($process->getOutput().PHP_EOL.$process->getErrorOutput());
        }

        $this->assertFileExists("{$temp->getTmpFolder()}/out/tables/weather.csv");
        $this->assertFileExists("{$temp->getTmpFolder()}/out/usage.json");

        $usage = json_decode(file_get_contents("{$temp->getTmpFolder()}/out/usage.json"));
        $this->assertCount(1, $usage);
        $apiCallsMetric = reset($usage);
        $this->assertEquals('API Calls', $apiCallsMetric->metric);
        $this->assertEquals(4, $apiCallsMetric->value);
    }

    public function testInvalidApiTokenShouldReturnUserError()
    {
        $temp = new Temp();
        $temp->initRunFolder();

        file_put_contents($temp->getTmpFolder() . '/config.json', json_encode([
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-main.coordinates',
                            'destination' => 'coordinates.csv'
                        ]
                    ]
                ],
            ],
            'parameters' => [
                '#apiToken' => 'INVALID_TOKEN',
                'conditions' => ['windSpeed']
            ]
        ]));

        mkdir($temp->getTmpFolder().'/in');
        mkdir($temp->getTmpFolder().'/in/tables');
        copy(__DIR__ . '/data/data.csv', $temp->getTmpFolder().'/in/tables/coordinates.csv');
        copy(__DIR__ . '/data/data.csv.manifest', $temp->getTmpFolder().'/in/tables/coordinates.csv.manifest');

        $process = new Process("php ".__DIR__."/../../../src/run.php --data=".$temp->getTmpFolder());
        $process->setTimeout(null);
        $process->run();
        var_dump($process->getOutput(), $process->getErrorOutput());
        $this->assertEquals(1, $process->getExitCode());

    }
}
