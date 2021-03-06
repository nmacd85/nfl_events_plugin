<?php

namespace Drupal\nfl_events_plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class ApiDataService.
 */
class ApiDataService {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Property for API key received from config factory.
   *
   * @var string
   */
  private $apiKey;

  /**
   * Property for ranking data returned from Team Ranking API.
   *
   * @var array
   */
  protected $rankingData;

  /**
   * Constructs a new ApiDataService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->rankingData = [];
    $this->configFactory = $config_factory;

    // Getting API Key to use in methods.
    $config = $this->configFactory->get('nfl_events_plugin.api_key');
    $this->apiKey = $config->get('api_key');
  }

  /**
   * Scoreboard API Request.
   *
   * @param string $start_date
   *   User URL input start date.
   * @param string $end_date
   *   User URL input end date.
   *
   * @return object
   *   JSON API results
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetchScoreboardData($start_date, $end_date) {
    // Build API Url.
    $uri = 'https://delivery.chalk247.com/scoreboard/NFL/';
    $uri .= $start_date . '/' . $end_date;
    $uri .= '.json?api_key=' . $this->apiKey;
    // Make the Scoreboard API Call.
    $request = $this->httpClient->request('GET', $uri);
    // Get request contents.
    $data = $request->getBody()->getContents();
    // Decode JSON for PHP use.
    $data = json_decode($data);

    return $data;
  }

  /**
   * Collect Team Ranking per Team ID.
   *
   * @param string $team_id
   *   Team ID comes from the Scoreboard API.
   *
   * @return array
   *   Custom team rank data.
   */
  public function fetchRankingData($team_id) {
    // Get Team Ranking Data.
    $data = $this->getRankingData();

    // Narrow down the $data so the foreach looks cleaner.
    $dataResults = $data->results->data;

    // Declaring array to avoid NULL errors.
    $team_rank_data = [
      'rank' => '',
      'adjusted_points' => '',
    ];

    // Loop $dataResults to get the.
    foreach ($dataResults as $team) {
      // Return rank and adjusted points.
      if ($team->team_id === $team_id) {
        $team_rank_data['rank'] = $team->rank;
        $team_rank_data['adjusted_points'] = $team->adjusted_points;
        break;
      }
    }

    return $team_rank_data;

  }

  /**
   * Team Ranking API Request.
   *
   * Saving data under $this->rankingData property so we don't make unnecessary
   * calls per scoreboard API call.
   */
  public function getRankingData() {
    if (empty($this->rankingData)) {
      // Build API Url.
      $uri = 'https://delivery.chalk247.com/team_rankings/NFL.json?api_key=';
      $uri .= $this->apiKey;
      // Make the Team Rankings API Call.
      $request = $this->httpClient->request('GET', $uri);
      // Get request contents.
      $data = $request->getBody()->getContents();
      // Decode JSON for PHP use.
      $data = json_decode($data);

      // Save in property for future use.
      $this->rankingData = $data;

      return $this->rankingData;
    }

    return $this->rankingData;
  }

}
