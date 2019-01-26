<?php
namespace davidhirtz\yii2\skeleton\widgets\forms;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Class CountryDropDownList.
 * @package davidhirtz\yii2\skeleton\form
 *
 * @property array $countries
 * @see CountryDropdown::getCountries()
 */
class CountryDropdown extends InputWidget
{
	/**
	 * @var bool
	 */
	public $allowEmpty=true;

	/**
	 * @var array
	 */
	public $options=['class'=>'form-control'];

	/**
	 * @var array
	 */
	private $_countries;

	/**
	 * @return string
	 */
	public function run()
	{
		$countries=$this->getCountries();
		asort($countries);

		if($this->allowEmpty)
		{
			ArrayHelper::setDefaultValue($this->options, 'prompt', '');
		}

		return $this->hasModel() ? Html::activeDropDownList($this->model, $this->attribute, $countries, $this->options) : Html::dropDownList($this->name, $this->value, $countries, $this->options);
	}

	/**
	 * @return array
	 */
	public function getCountries()
	{
		if($this->_countries===null)
		{
			$this->_countries=[
				'Afghanistan',
				'Albania',
				'Algeria',
				'Andorra',
				'Angola',
				'Antigua and Barbuda',
				'Argentina',
				'Armenia',
				'Australia',
				'Austria',
				'Azerbaijan',
				'Bahamas',
				'Bahrain',
				'Bangladesh',
				'Barbados',
				'Belarus',
				'Belgium',
				'Belize',
				'Benin',
				'Bhutan',
				'Bolivia',
				'Bosnia and Herzegovina',
				'Botswana',
				'Brazil',
				'Brunei',
				'Bulgaria',
				'Burkina Faso',
				'Burundi',
				'Cambodia',
				'Cameroon',
				'Canada',
				'Cape Verde',
				'Central African Republic',
				'Chad',
				'Chile',
				'China',
				'Colombi',
				'Comoros',
				'Congo (Brazzaville)',
				'Congo',
				'Costa Rica',
				'Cote d\'Ivoire',
				'Croatia',
				'Cuba',
				'Cyprus',
				'Czech Republic',
				'Denmark',
				'Djibouti',
				'Dominica',
				'Dominican Republic',
				'East Timor (Timor Timur)',
				'Ecuador',
				'Egypt',
				'El Salvador',
				'Equatorial Guinea',
				'Eritrea',
				'Estonia',
				'Ethiopia',
				'Fiji',
				'Finland',
				'France',
				'Gabon',
				'Gambia, The',
				'Georgia',
				'Germany',
				'Ghana',
				'Greece',
				'Grenada',
				'Guatemala',
				'Guinea',
				'Guinea-Bissau',
				'Guyana',
				'Haiti',
				'Honduras',
				'Hungary',
				'Iceland',
				'India',
				'Indonesia',
				'Iran',
				'Iraq',
				'Ireland',
				'Israel',
				'Italy',
				'Jamaica',
				'Japan',
				'Jordan',
				'Kazakhstan',
				'Kenya',
				'Kiribati',
				'Korea, North',
				'Korea, South',
				'Kuwait',
				'Kyrgyzstan',
				'Laos',
				'Latvia',
				'Lebanon',
				'Lesotho',
				'Liberia',
				'Libya',
				'Liechtenstein',
				'Lithuania',
				'Luxembourg',
				'Macedonia',
				'Madagascar',
				'Malawi',
				'Malaysia',
				'Maldives',
				'Mali',
				'Malta',
				'Marshall Islands',
				'Mauritania',
				'Mauritius',
				'Mexico',
				'Micronesia',
				'Moldova',
				'Monaco',
				'Mongolia',
				'Morocco',
				'Mozambique',
				'Myanmar',
				'Namibia',
				'Nauru',
				'Nepa',
				'Netherlands',
				'New Zealand',
				'Nicaragua',
				'Niger',
				'Nigeria',
				'Norway',
				'Oman',
				'Pakistan',
				'Palau',
				'Panama',
				'Papua New Guinea',
				'Paraguay',
				'Peru',
				'Philippines',
				'Poland',
				'Portugal',
				'Qatar',
				'Romania',
				'Russia',
				'Rwanda',
				'Saint Kitts and Nevis',
				'Saint Lucia',
				'Saint Vincent',
				'Samoa',
				'San Marino',
				'Sao Tome and Principe',
				'Saudi Arabia',
				'Senegal',
				'Serbia and Montenegro',
				'Seychelles',
				'Sierra Leone',
				'Singapore',
				'Slovakia',
				'Slovenia',
				'Solomon Islands',
				'Somalia',
				'South Africa',
				'Spain',
				'Sri Lanka',
				'Sudan',
				'Suriname',
				'Swaziland',
				'Sweden',
				'Switzerland',
				'Syria',
				'Taiwan',
				'Tajikistan',
				'Tanzania',
				'Thailand',
				'Togo',
				'Tonga',
				'Trinidad and Tobago',
				'Tunisia',
				'Turkey',
				'Turkmenistan',
				'Tuvalu',
				'Uganda',
				'Ukraine',
				'United Arab Emirates',
				'United Kingdom',
				'United States',
				'Uruguay',
				'Uzbekistan',
				'Vanuatu',
				'Vatican City',
				'Venezuela',
				'Vietnam',
				'Yemen',
				'Zambia',
				'Zimbabwe'
			];
		}

		return array_combine($this->_countries, $this->_countries);
	}

	/**
	 * @param array $countries
	 */
	public function setCountries($countries)
	{
		$this->_countries=$countries;
	}
}