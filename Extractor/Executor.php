<?php
/**
 * Created by IntelliJ IDEA.
 * User: JakubM
 * Date: 04.09.14
 * Time: 15:19
 */

namespace Keboola\ForecastIoExtractorBundle\Extractor;


use Geocoder\Geocoder;
use Geocoder\HttpAdapter\GuzzleHttpAdapter;
use Geocoder\Provider\ChainProvider;
use Geocoder\Provider\GoogleMapsProvider;
use Keboola\ForecastIoExtractorBundle\ForecastTools\Forecast;
use Keboola\ForecastIoExtractorBundle\ForecastTools\Response;

class Executor
{
	protected $sharedStorage;
	protected $forecastIoKey;
	protected $googleApiKey;

	public function __construct(SharedStorage $sharedStorage, $googleApiKey, $forecastIoKey)
	{
		$this->sharedStorage = $sharedStorage;
		$this->forecastIoKey = $forecastIoKey;
		$this->googleApiKey = $googleApiKey;
	}


	public function getForecast($coords, $date)
	{
		$dateHour = date('YmdH', strtotime($date));

		$savedForecasts = $this->sharedStorage->getSavedForecasts($coords, $date);

		$result = array();
		$apiData = array();
		foreach ($coords as $loc => $c) {
			$key = md5(sprintf('%s.%s.%s', $dateHour, $c['latitude'], $c['longitude']));
			$result[$c['latitude'] . ':' . $c['longitude']] = array(
				'location' => $loc,
				'latitude' => $c['latitude'],
				'longitude' => $c['longitude']
			);
			if (!isset($savedForecasts[$key])) {
				$apiData[] = array(
					'latitude' => $c['latitude'],
					'longitude' => $c['longitude'],
					'units' => 'si'
				);
			} else {
				$result[$c['latitude'] . ':' . $c['longitude']]['temperature'] = $savedForecasts[$key]['temperature'];
				$result[$c['latitude'] . ':' . $c['longitude']]['weather'] = $savedForecasts[$key]['weather'];
			}
		}

		$forecastToSave = array();
		if (count($apiData)) {
			$forecast = new Forecast($this->forecastIoKey, 1);
			$response = $forecast->getData($apiData);
			foreach ($response as $r) {
				/** @var Response $r */
				$curr = $r->getCurrently();
				$result[$r->getLatitude() . ':' . $r->getLongitude()]['temperature'] = $curr->getTemperature();
				$result[$r->getLatitude() . ':' . $r->getLongitude()]['weather'] = $curr->getSummary();
				$forecastToSave[] = array(
					md5(sprintf('%s.%s.%s', $dateHour, $r->getLatitude(), $r->getLongitude())),
					$date,
					$r->getLatitude(),
					$r->getLongitude(),
					$curr->getTemperature(),
					$curr->getSummary()
				);
			}
			if (count($forecastToSave)) {
				$this->sharedStorage->updateTable(SharedStorage::FORECASTS_TABLE_NAME, $forecastToSave);
			}
		}

		$finalResult = array();
		foreach ($coords as $loc => $c) {
			$res = $result[$c['latitude'] . ':' . $c['longitude']];
			unset($res['location']);
			$finalResult[$loc] = $res;
		}
		return $finalResult;
	}


	public function getCoordinates($locations)
	{
		$savedLocations = $this->sharedStorage->getSavedLocations($locations);

		$result = array();
		$locationsToSave = array();
		foreach ($locations as $loc) {
			if (!isset($savedLocations[$loc])) {
				$location = $this->getAddressCoordinates($loc);
				$coords = $location? $this->getForecastLocation($location) : array('latitude' => '-', 'longitude' => '-');
				$savedLocations[$loc] = $coords;
				$locationsToSave[] = array($loc, $coords['latitude'], $coords['longitude']);
			}
			$result[$loc] = $savedLocations[$loc];
		}

		if (count($locationsToSave)) {
			$this->sharedStorage->updateTable(SharedStorage::LOCATIONS_TABLE_NAME, $locationsToSave);
		}
		return $result;
	}

	public function getAddressCoordinates($address)
	{
		$adapter = new GuzzleHttpAdapter();
		$chain = new ChainProvider(array(
			new GoogleMapsProvider($adapter, null, null, true, $this->googleApiKey)
		));
		$geocoder = new Geocoder($chain);
		try {
			$geocode = $geocoder->geocode($address);
			return $geocode->getCoordinates();
		} catch (\Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}

	public function getForecastLocation($coords)
	{
		return array(
			'latitude' => round($coords[0], 1),
			'longitude' => round($coords[1], 1)
		);
	}

} 