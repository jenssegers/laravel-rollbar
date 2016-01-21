<?php

namespace Jenssegers\Rollbar;

use Illuminate\Console\Command;

class RollbarDeployNotify extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rollbar:deploynotify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify rollbar of a deployment';
    private $accesssToken;

    public function __construct($config)
    {
        parent::__construct();
        $this->accesssToken = $config['access_token'];
    }

    public function fire()
    {
        $this->comment("hello");

        $environment = env('APP_ENV');
        $localUserName = exec('whoami');
        $revision = exec('git log -n 1 --pretty=format:"%H"');

        $fields_string = "";
        $url = "https://api.rollbar.com/api/1/deploy/";
        $fields = [
            'environment' => urlencode($environment),
            'local_username' => urlencode($localUserName),
            'revision' => urlencode($revision),
            'access_token' => urlencode($this->accesssToken),
        ];

        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        $result = curl_exec($ch);

        curl_close($ch);
    }
}
