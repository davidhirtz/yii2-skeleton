<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $properties array list of properties (property => [type, name. comment]) */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;

/**
 * <?= $className ."\n" ?>
 * @package <?= $generator->ns ."\n" ?>
 *
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}"  . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . ' {@link ' . $className . '::get' . $name  . "()}\n" ?>
<?php endforeach; ?>
<?php endif; ?>
*/
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [<?= empty($rules) ? '' : ("\n            " . implode(",\n            ", $rules) . ",\n        ") ?>];
    }
<?php foreach ($relations as $name => $relation): ?>

    /**
     * @return <?= $relationsClassHints[$name] . "\n" ?>
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>
<?php if ($queryClassName): ?>
    <?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
    ?>
    /**
     * @return <?= $queryClassFullName ?>
     */
    public static function find()
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
    <?php foreach ($labels as $name => $label): ?>
        <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
    <?php endforeach; ?>
    ];
    }

    /**
     * @string
     */
    public function formName(): string
    {
        return '<?= $className ?>';
    }

    /**
     * @string
     */
    public static function tableName(): string
    {
        return '<?= $generator->generateTableName('{{%' . $tableName . '}}') ?>';
    }
}