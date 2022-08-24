<?php

namespace App\Http\Controllers;

use App\Jobs\SendLogJob;
use Google\Cloud\Logging\LoggingClient;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index()
    {
        return view('log');
    }

    public function store(Request $request)
    {
//        $trace = (new \Exception())->getTrace();
        $labels = [
            'logContent' => $request->input('log_content'),
            'level' => $request->input('log_level'),
            'user_id' => $request->input('user_id'),
            'merchant_id' => $request->input('merchant_id'),
            'env' => 'local',
            'class' => __CLASS__,
            'method' => __METHOD__,
            'line' => (string)__LINE__,
        ];


        SendLogJob::dispatch($request->input('log_name'), $labels);
    }

    public function search(Request $request)
    {
        $logging = new LoggingClient([
            'projectId' => env('CLOUD_GOOGLE_PROJECT_ID'),
            'keyFile' => json_decode(file_get_contents(base_path(env('CLOUD_GOOGLE_KEY_FILE'))), true)
        ]);

//        dd($this->listEntries('crack-parser-359620', 'PAYLIVRE'));

        $loggerFullName = sprintf('projects/%s/logs/%s', 'crack-parser-359620', 'PAYLIVRE');

        $fromDate = $request->input('from_date') . $request->input('from_time');

        if ($fromDate) {
            $fromDate = date(\DateTime::RFC3339, strtotime($request->input('from_date') . $request->input('from_time')));
        }


        $arguments = [];

        $arguments[] = $this->buildArgument('logName', '=', $loggerFullName);
        $arguments[] = $this->buildArgument('timestamp', '>=', $fromDate ?? null);
        $arguments[] = $this->buildArgument('textPayload', '=', $request->input('log_name'));
        $arguments[] = $this->buildArgument('labels.merchant_id', '=', $request->input('merchant_id'));
        $arguments[] = $this->buildArgument('labels.user_id', '=', $request->input('user_id'));
//        $arguments[] = $this->buildArgument('severity', '=', 'ERROR');


        $arguments = array_values(array_filter($arguments));
        $query = $this->getQueryByArguments($arguments);
        $options = [
            'filter' => $query,
        ];
        $entries = $logging->entries($options);
        $response = [];

        foreach ($entries as $entry) {
            $response[] = $entry->info();
        }

        return $response;
    }

    public function indexSearch()
    {
        return view('search');
    }

    public function getQueryByArguments(array $arguments): string
    {
        $query = '';
        foreach ($arguments as $key => $argument) {
            $query .= sprintf('%s %s "%s"',
                $argument['name'],
                $argument['operator'],
                $argument['value']);


            if ($key !== (count($arguments) - 1)) {
                $query .= ' AND ';
            }
        }

        return $query;
    }


    public function buildArgument(string $name, $operator, $value)
    {
        if (!$value || !$name || !$operator) {
            return null;
        }

        return [
            'name' => $name,
            'operator' => $operator,
            'value' => $value
        ];
    }

    function listEntries($projectId, $loggerName)
    {
        $logging = new LoggingClient(['projectId' => $projectId, 'keyFile' => json_decode(file_get_contents(base_path('google/google-credentials.json')), true)]);
        $loggerFullName = sprintf('projects/%s/logs/%s', $projectId, $loggerName);
        $oneDayAgo = date(\DateTime::RFC3339, strtotime('-100 hours'));
        $filter = sprintf(
            'logName = "%s" AND timestamp >= "%s"',
            $loggerFullName,
            $oneDayAgo
        );
        $options = [
            'filter' => $filter,
        ];

        $entries = $logging->entries($options);
        $response = [];

        foreach ($entries as $entry) {
            $response[] = $entry->info();
        }

        return $response;
    }
}
