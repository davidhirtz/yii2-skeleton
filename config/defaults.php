<?php
return [
	'components'=>[
		'assetManager'=>[
			'class'=>'davidhirtz\yii2\skeleton\web\AssetManager',
			'bundles'=>[
				'davidhirtz\yii2\lazysizes\AssetBundle'=>[
					'sourcePath'=>null,
					'js'=>[
						[
							'//cdnjs.cloudflare.com/ajax/libs/lazysizes/4.0.4/lazysizes.min.js',
							'position'=>\davidhirtz\yii2\skeleton\web\View::POS_HEAD,
							'async'=>true,
						],
					],
				],
				// Overrides Bootstrap 3 dependency, this can probably removed in the future
				'dosamigos\fileupload\FileUploadAsset'=>[
					'depends'=>[
						'yii\web\JqueryAsset',
						'yii\bootstrap4\BootstrapAsset',
					],
				],
				'yii\bootstrap4\BootstrapAsset'=>[
					'sourcePath'=>null,
					'css'=>[],
				],
				'yii\web\JqueryAsset'=>[
					'sourcePath'=>null,
					'js'=>[
						'//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js',
					],
				],
				'yii\jui\JuiAsset'=>[
					'js'=>[],
				],
			],
			'combineOptions'=>[
				'jsCompressor'=>'gulp combine-js --src {from} --output {to}',
				'cssCompressor'=>'gulp combine-css --src {from} --output {to}',
			],
		],
		'authClientCollection'=>[
			'class'=>'yii\authclient\Collection',
			'clients'=>[
				'facebook'=>[
					'class'=>'davidhirtz\yii2\skeleton\auth\clients\Facebook',
				],
			],
		],
		'authManager'=>[
			'class'=>'yii\rbac\DbManager',
			'cache'=>'cache',
		],
//		'cache'=>[
//			'class'=>'yii\caching\FileCache',
//		],
//		'db'=>[
//			'class'=>'yii\db\Connection',
//			'enableSchemaCache'=>true,
//			'charset'=>'utf8mb4',
//		],
//		'i18n'=>[
//			'class'=>'davidhirtz\yii2\skeleton\i18n\I18N',
//			'translations'=>[
//				'app'=>[
//					'class'=>'yii\i18n\PhpMessageSource',
//					'sourceLanguage'=>'en-US',
//					'basePath'=>'@app/messages',
//				],
//			],
//		],
		'log'=>[
			'traceLevel'=>YII_DEBUG ? 3 : 0,
			'targets'=>[
				[
					'class'=>'yii\log\FileTarget',
					'levels'=>['error', 'warning'],
					'except'=>['yii\web\HttpException:*'],
				],
			],
		],
		'urlManager'=>[
			'class'=>'davidhirtz\yii2\skeleton\web\UrlManager',
			'defaultRules'=>[
				'sitemap.xml'=>'/sitemap/index',
			],
		],
//		'view'=>[
//			'class'=>'davidhirtz\yii2\skeleton\web\View',
//		],
	],
	'modules'=>[
//		'admin'=>[
//			'class'=>'davidhirtz\yii2\skeleton\modules\admin\Module',
//		],
	],
	'params'=>[
		'app.email'=>'',
		'app.cookieDuration'=>2592000,

		'user.nameMinLength'=>3,
		'user.nameMaxLength'=>32,
		'user.passwordMinLength'=>5,
		'user.enableSignup'=>false,
		'user.unconfirmedLogin'=>true,
		'user.resetPassword'=>true,

		'facebook.appId'=>null,
		'facebook.secret'=>null,

		'google.trackingId'=>null,
	],
];