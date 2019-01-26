<?php
namespace davidhirtz\yii2\skeleton\console\controllers;

use yii\console\Controller;
use Yii;
use yii\helpers\Console;

/**
 * Runs application configuration.
 * @package davidhirtz\yii2\skeleton\console\controllers
 */
class ConfigController extends Controller
{
	/**
	 * @var string 
	 */
	public $defaultAction='init';
	
	/**
	 * @var string
	 */
	public $paramsFile='@app/config/params.php';

	/**
	 * @var string
	 */
	public $dbFile='@app/config/db.php';

	/**
	 * Setup application.
	 *
	 * @param bool $replace
	 * @throws \Exception
	 */
	public function actionInit($replace=false)
	{
		$this->actionCookie($replace);
		$this->actionDb($replace);
	}


	/**
	 * Creates cookie validation key.
	 *
	 * @param bool $replace
	 * @throws \Exception
	 */
	public function actionCookie($replace=true)
	{
		$params=$this->getConfigFile($this->paramsFile);
		$found=!empty($params['cookieValidationKey']);

		if(!$found || $replace)
		{
			if($this->confirm($found ? 'Override existing cookie validation key?' : 'Generate cookie validation key?', !$found))
			{
				$params['cookieValidationKey']=static::generateCookieValidationKey();
				$this->setConfigFile($this->paramsFile, $params);
			}
		}
	}

	/**
	 * Creates database connection credentials.
	 *
	 * @param bool $replace
	 * @throws \Exception
	 */
	public function actionDb($replace=true)
	{
		$db=$this->getConfigFile($this->dbFile);
		$found=!empty($db);

		if(!$found || $replace)
		{
			if($this->confirm($found ? 'Override existing database connection credentials?' : 'Generate database connection credentials?', !$found))
			{
				$dsn=[];
				$db['dsn']='';

				$dsn['mysql:host']=$this->prompt('Enter database host:', ['default'=>'localhost']);
				$dsn['port']=$this->prompt('Enter port or leave empty:');
				$dsn['dbname']=$this->prompt('Enter database name:', ['required'=>true]);

				foreach($dsn as $name=>$value)
				{
					if($value)
					{
						$db['dsn'].=";{$name}={$value}";
					}
				}

				$db['dsn']=trim($db['dsn'], ';');

				$db['username']=$this->prompt('Enter username:', ['default'=>$dsn['dbname'], 'required'=>true]);

				$this->stdout('Enter password: ');
				$db['password']=\Seld\CliPrompt\CliPrompt::hiddenPrompt();

				$this->setConfigFile($this->dbFile, $db);
			}
		}
	}

	/**
	 * Adds or updates give parameter in config.
	 *
	 * @param string $param
	 * @param mixed $value
	 * @throws \Exception
	 */
	public function actionCreate($param, $value)
	{
		$params=$this->getConfigFile($this->paramsFile);

		if(!isset($params[$param]) || $this->confirm('Parameter already exists, override?', false))
		{
			$boolean=filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			$params[$param]=$boolean!==null ? $boolean : (filter_var($value, FILTER_VALIDATE_INT)!==false ? intval($value) : $value);

			$this->setConfigFile($this->paramsFile, $params);
		}
	}

	/**
	 * Removes given parameter from config.
	 *
	 * @param string $param
	 * @throws \Exception
	 */
	public function actionDelete($param)
	{
		$params=$this->getConfigFile($this->paramsFile);

		if(isset($params[$param]))
		{
			unset($params[$param]);
			$this->setConfigFile($this->paramsFile, $params);
		}
	}


	/**
	 * @return string
	 * @throws \Exception
	 */
	protected static function generateCookieValidationKey()
	{
		if(!extension_loaded('openssl'))
		{
			throw new \Exception('The OpenSSL PHP extension is required by Yii2.');
		}
		$length=32;
		$bytes=openssl_random_pseudo_bytes($length);
		return strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
	}

	/**
	 * @param $file
	 * @return array
	 */
	protected function getConfigFile($file)
	{
		$file=Yii::getAlias($file);
		return is_file($file) ? require($file) : [];
	}

	/**
	 * @param $file
	 * @param $config
	 * @throws \Exception
	 */
	protected function setConfigFile($file, $config)
	{
		$file=Yii::getAlias($file);

		$config=var_export($config, true);
		$config=preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $config);
		$config=preg_split("/\r\n|\n|\r/", $config);
		$config=preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $config);
		$export=join(PHP_EOL, array_filter(["["]+$config));
		$date=date('c');

		file_put_contents($file, <<<EOL
<?php
/**
 * @version $date
 */
return $export;
EOL
		);

		$this->stdout($file. ' was updated.'.PHP_EOL, Console::FG_GREEN);
	}
}