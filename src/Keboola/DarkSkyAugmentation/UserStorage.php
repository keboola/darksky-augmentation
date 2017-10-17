<?php
namespace Keboola\DarkSkyAugmentation;

use Keboola\Csv\CsvFile;

class UserStorage
{
    const METADATA_DESCRIPTION = 'KBC.description';

    protected static $columns = ['primary', 'latitude', 'longitude', 'date', 'key', 'value'];
    protected static $primaryKey = ['primary'];

    protected $outputFile;
    protected $file;

    public function __construct($outputFile)
    {
        $this->outputFile = $outputFile;
    }

    public function save($data)
    {
        if (!$this->file) {
            $this->file = new CsvFile($this->outputFile);
            $this->file->writeRow(self::$columns);

            file_put_contents("$this->outputFile.manifest", json_encode([
                'incremental' => true,
                'primary_key' => self::$primaryKey,
                'column_metadata' => [
                    'primary' => [
                        [
                            'key' => self::METADATA_DESCRIPTION,
                            'value' => 'Hash of latitude, longitude, date and key used for incremental saving of data',
                        ]
                    ],
                    'latitude' => [
                        [
                            'key' => self::METADATA_DESCRIPTION,
                            'value' => 'Latitude of location',
                        ]
                    ],
                    'longitude' => [
                        [
                            'key' => self::METADATA_DESCRIPTION,
                            'value' => 'Longitude of location',
                        ]
                    ],
                    'date' => [
                        [
                            'key' => self::METADATA_DESCRIPTION,
                            'value' => 'Date and time of weather conditions validity',
                        ]
                    ],
                    'key' => [
                        [
                            'key' => self::METADATA_DESCRIPTION,
                            'value' => 'Name of weather condition',
                        ]
                    ],
                    'value' => [
                        [
                            'key' => self::METADATA_DESCRIPTION,
                            'value' => 'Value of weather condition',
                        ]
                    ]
                ]
            ]));
        }

        if (!is_array($data)) {
            $data = (array)$data;
        }
        $dataToSave = [];
        foreach (self::$columns as $c) {
            $dataToSave[$c] = isset($data[$c]) ? $data[$c] : null;
        }

        $this->file->writeRow($dataToSave);
    }
}
