<?php

namespace Keboola\DarkSkyAugmentation\Tests;

use Keboola\Csv\CsvFile;
use Keboola\DarkSkyAugmentation\Augmentation;
use Keboola\DarkSkyAugmentation\Exception;
use Keboola\DarkSkyAugmentation\InvalidApiKeyException;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;

class AugmentationTest extends TestCase
{
    /** @var  Temp */
    protected $temp;
    /** @var  Augmentation */
    protected $app;
    protected $outputFile;
    protected $usageFile;

    public function setUp()
    {
        $outputTable = 't' . uniqid();
        $usageFile = 'usage.json';

        $this->temp = new Temp();
        $this->temp->initRunFolder();

        $this->app = new \Keboola\DarkSkyAugmentation\Augmentation(
            DARKSKY_KEY,
            $this->temp->getTmpFolder() . "/$outputTable",
            $this->temp->getTmpFolder() . "/$usageFile"
        );

        $this->outputFile = "{$this->temp->getTmpFolder()}/$outputTable";
        $this->usageFile = "{$this->temp->getTmpFolder()}/$usageFile";
        copy(__DIR__ . '/data/data.csv', $this->temp->getTmpFolder() . '/data1.csv');
    }

    public function testAugmentationForDefinedDatesWithDailyGranularity()
    {
        $this->app->process($this->temp->getTmpFolder() . '/data1.csv', ['temperatureMax', 'windSpeed']);
        $this->assertFileExists($this->outputFile);
        $data = new CsvFile($this->outputFile);
        $this->assertCount(7, $data);
        $location1Count = 0;
        $location2Count = 0;
        foreach ($data as $row) {
            if ($row[1] == 49.191 && $row[2] == 16.611) {
                $location1Count++;
            }
            if ($row[1] == 50.071 && $row[2] == 14.423) {
                $location2Count++;
            }
        }
        $this->assertEquals(4, $location1Count);
        $this->assertEquals(2, $location2Count);

        $usage = json_decode(file_get_contents($this->usageFile));
        $this->assertCount(1, $usage);
        $apiCallsMetric = reset($usage);
        $this->assertEquals('API Calls', $apiCallsMetric->metric);
        $this->assertEquals(4, $apiCallsMetric->value);
    }

    public function testAugmentationForFutureDay()
    {
        $future = date('Y-m-d', strtotime('+2 day'));
        $rows = [
            'lat,lon,time',
            '"49.191","16.611","' . $future .  '"',
        ];
        file_put_contents($this->temp->getTmpFolder() . '/data1.csv', implode("\n", $rows));
        $this->app->process($this->temp->getTmpFolder() . '/data1.csv', ['temperatureMax', 'windSpeed']);
        $this->assertFileExists($this->outputFile);
        $data = iterator_to_array(new CsvFile($this->outputFile));
        $this->assertCount(3, $data);
        $locationCount = 0;
        foreach ($data as $row) {
            if ($row[1] == 49.191 && $row[2] == 16.611) {
                $locationCount++;
            }
        }
        $this->assertEquals(2, $locationCount);

        $usage = json_decode(file_get_contents($this->usageFile));
        $this->assertCount(1, $usage);
        $apiCallsMetric = reset($usage);
        $this->assertEquals('API Calls', $apiCallsMetric->metric);
        $this->assertEquals(1, $apiCallsMetric->value);
    }

    public function testAugmentationForVeryFarFutureDay()
    {
        $future = date('Y-m-d', strtotime('+2000 year'));
        $rows = [
            'lat,lon,time',
            '"49.191","16.611","' . $future .  '"',
            '"49.191","16.611","' . date('Y-m-d') .  '"',
        ];
        file_put_contents($this->temp->getTmpFolder() . '/data1.csv', implode("\n", $rows));
        $this->app->process($this->temp->getTmpFolder() . '/data1.csv', ['temperatureMax', 'windSpeed']);
        $this->assertFileExists($this->outputFile);
        $data = iterator_to_array(new CsvFile($this->outputFile));
        $this->assertCount(3, $data);
        $locationCount = 0;
        foreach ($data as $row) {
            if ($row[1] == 49.191 && $row[2] == 16.611) {
                $locationCount++;
            }
        }
        $this->assertEquals(2, $locationCount);

        $usage = json_decode(file_get_contents($this->usageFile));
        $this->assertCount(1, $usage);
        $apiCallsMetric = reset($usage);
        $this->assertEquals('API Calls', $apiCallsMetric->metric);
        $this->assertEquals(2, $apiCallsMetric->value);
    }

    public function testAugmentationForDefinedDatesWithDailyHourly()
    {
        $this->app->process(
            $this->temp->getTmpFolder() . '/data1.csv',
            ['temperature', 'windSpeed'],
            Augmentation::TEMPERATURE_UNITS_SI,
            Augmentation::GRANULARITY_HOURLY
        );
        $this->assertFileExists($this->outputFile);
        $data = new CsvFile($this->outputFile);
        $this->assertCount(1 + 24 * 3 * 2, $data);
        $location1Count = 0;
        $location2Count = 0;
        foreach ($data as $row) {
            if ($row[1] == 49.191 && $row[2] == 16.611) {
                $location1Count++;
            }
            if ($row[1] == 50.071 && $row[2] == 14.423) {
                $location2Count++;
            }
        }
        $this->assertEquals(96, $location1Count);
        $this->assertEquals(48, $location2Count);

        $usage = json_decode(file_get_contents($this->usageFile));
        $this->assertCount(1, $usage);
        $apiCallsMetric = reset($usage);
        $this->assertEquals('API Calls', $apiCallsMetric->metric);
        $this->assertEquals(4, $apiCallsMetric->value);
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidInputCsvShouldThrowUserError()
    {
        $outputTable = 't' . uniqid();
        $usageFile = 'usage.json';

        $this->temp = new Temp();
        $this->temp->initRunFolder();

        $this->app = new \Keboola\DarkSkyAugmentation\Augmentation(
            DARKSKY_KEY,
            $this->temp->getTmpFolder() . "/$outputTable",
            $this->temp->getTmpFolder() . "/$usageFile"
        );

        $this->outputFile = "{$this->temp->getTmpFolder()}/$outputTable";
        $this->usageFile = "{$this->temp->getTmpFolder()}/$usageFile";
        copy(__DIR__ . '/data/invalid-data.csv', $this->temp->getTmpFolder() . '/data1.csv');


        $this->app->process($this->temp->getTmpFolder() . '/data1.csv', ['temperatureMax', 'windSpeed']);
    }

    public function testInvalidTokenShouldThrowUserError()
    {
        $outputTable = 't' . uniqid();
        $usageFile = 'usage.json';

        $this->temp = new Temp();
        $this->temp->initRunFolder();

        $app = new \Keboola\DarkSkyAugmentation\Augmentation(
            'INVALID_KEY',
            $this->temp->getTmpFolder() . "/$outputTable",
            $this->temp->getTmpFolder() . "/$usageFile"
        );

        $this->outputFile = "{$this->temp->getTmpFolder()}/$outputTable";
        $this->usageFile = "{$this->temp->getTmpFolder()}/$usageFile";
        copy(__DIR__ . '/data/data.csv', $this->temp->getTmpFolder() . '/data1.csv');

        try {
            $app->process(
                $this->temp->getTmpFolder() . '/data1.csv',
                ['temperature', 'windSpeed']
            );
            $this->fail('Exception should be thrown');
        } catch (Exception $e) {
            $this->assertInstanceOf('Keboola\DarkSkyAugmentation\InvalidApiKeyException', $e);
        }
    }
}
