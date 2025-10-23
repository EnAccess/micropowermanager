<?php

namespace App\Console\Commands;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\Reflection\DocBlock;
use Barryvdh\Reflection\DocBlock\Context;
use Barryvdh\Reflection\DocBlock\Serializer as DocBlockSerializer;
use Barryvdh\Reflection\DocBlock\Tag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * This file is a copy of the great laravel-ide-helper.
 * https://github.com/barryvdh/laravel-ide-helper/blob/9ef25f60e70ced86f687ef6b9ffd9ac74a7c388a/src/Console/ModelsCommand.php.
 *
 * The original implementation lacks some configuration flexibility.
 * It also generates extensive PHPDocs that are largely unnecessary for Laravel 11+,
 * and may become outdated or misleading over time.
 *
 * Our main goal is to automatically generate @property and @property-read
 * Model annotations that accurately reflect our database schema.
 *
 * Therefore, we have customized the logic as follows:
 * - Skip generation of the Eloquent helper file
 * - Omit generic @mixin annotations from models
 * - Hardcode `bit` and `tinyint` as `bool` as this is the behaviour of Laravel with MySQL
 * - Hardcode `decimal` as `float`
 * - Adds handling for `date` and `datetime` treating them as `timestamp`.
 *   Note: Should probably be adding $casts to models where the use of `date` or `datetime`
 *   is actually desired. A good example of this is `birth_date`.
 *
 * WARNING: Running this tool is not considered safe. Please always
 * verify the output.
 */
class MpmModelsCommand extends ModelsCommand {
    // protected $signature = 'mpm:ide-helper';
    protected $name = 'mpm-ide-helper:models';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        $this->filename = $this->laravel['config']->get('ide-helper.models_filename', '_ide_helper_models.php');
        $filename = $this->option('filename') ?? $this->filename;
        $this->write = $this->option('write');
        $this->write_mixin = $this->option('write-mixin');
        $this->dirs = array_merge(
            $this->laravel['config']->get('ide-helper.model_locations', []),
            $this->option('dir')
        );
        $model = $this->argument('model');
        $ignore = $this->option('ignore');
        $this->reset = $this->option('reset');
        $this->phpstorm_noinspections = $this->option('phpstorm-noinspections');
        $this->write_model_magic_where = $this->laravel['config']->get('ide-helper.write_model_magic_where', true);
        $this->write_model_external_builder_methods = $this->laravel['config']->get('ide-helper.write_model_external_builder_methods', true);
        $this->write_model_relation_count_properties =
            $this->laravel['config']->get('ide-helper.write_model_relation_count_properties', true);
        $this->write_model_relation_exists_properties =
            $this->laravel['config']->get('ide-helper.write_model_relation_exists_properties', false);

        $this->write = $this->write_mixin ? true : $this->write;
        // If filename is default and Write is not specified, ask what to do
        if (!$this->write && $filename === $this->filename && !$this->option('nowrite')) {
            if (
                $this->confirm(
                    "Do you want to overwrite the existing model files? Choose no to write to $filename instead"
                )
            ) {
                $this->write = true;
            }
        }

        $this->dateClass = class_exists(Carbon::class)
            ? '\\'.get_class(Carbon::now())
            : '\Illuminate\Support\Carbon';

        $content = $this->generateDocs($model, $ignore);

        if (!$this->write || $this->write_mixin) {
            $written = $this->files->put($filename, $content);
            if ($written !== false) {
                $this->info("Model information was written to $filename");
            } else {
                $this->error("Failed to write model information to $filename");
            }
        }

        $helperFilename = $this->config->get('ide-helper.filename');
        $writeHelper = $this->option('write-eloquent-helper');

        // if (!$writeHelper && !$this->files->exists($helperFilename) && ($this->write || $this->write_mixin)) {
        //     if ($this->confirm("{$helperFilename} does not exist.
        //     Do you want to generate a minimal helper to generate the Eloquent methods?")) {
        //         $writeHelper = true;
        //     }
        // }

        // if ($writeHelper) {
        //     $generator = new Generator($this->config, $this->view, $this->getOutput());
        //     $content = $generator->generateEloquent();
        //     $written = $this->files->put($helperFilename, $content);
        //     if ($written !== false) {
        //         $this->info("Eloquent helper was written to $helperFilename");
        //     } else {
        //         $this->error("Failed to write eloquent helper to $helperFilename");
        //     }
        // }
    }

    /**
     * @param string $class
     *
     * @return string
     */
    protected function createPhpDocs($class) {
        $reflection = new \ReflectionClass($class);
        $namespace = $reflection->getNamespaceName();
        $classname = $reflection->getShortName();
        $originalDoc = $reflection->getDocComment();
        $keyword = $this->getClassKeyword($reflection);
        $interfaceNames = array_diff_key(
            $reflection->getInterfaceNames(),
            $reflection->getParentClass()->getInterfaceNames()
        );

        $phpdoc = new DocBlock($reflection, new Context($namespace));
        if ($this->reset) {
            $phpdoc->setText(
                (new DocBlock($reflection, new Context($namespace)))->getText()
            );
            foreach ($phpdoc->getTags() as $tag) {
                if (
                    in_array($tag->getName(), ['property', 'property-read', 'property-write', 'method', 'mixin'])
                    || ($tag->getName() === 'noinspection' && in_array($tag->getContent(), ['PhpUnnecessaryFullyQualifiedNameInspection', 'PhpFullyQualifiedNameUsageInspection']))
                ) {
                    $phpdoc->deleteTag($tag);
                }
            }
        }

        $properties = [];
        $methods = [];
        foreach ($phpdoc->getTags() as $tag) {
            $name = $tag->getName();
            if ($name == 'property' || $name == 'property-read' || $name == 'property-write') {
                // @phpstan-ignore method.notFound
                $properties[] = $tag->getVariableName();
            } elseif ($name == 'method') {
                // @phpstan-ignore method.notFound
                $methods[] = $tag->getMethodName();
            }
        }

        foreach ($this->properties as $name => $property) {
            $name = "\$$name";

            if ($this->hasCamelCaseModelProperties()) {
                $name = Str::camel($name);
            }

            if (in_array($name, $properties)) {
                continue;
            }
            if ($property['read'] && $property['write']) {
                $attr = 'property';
            } elseif ($property['write']) {
                $attr = 'property-write';
            } else {
                $attr = 'property-read';
            }

            $tagLine = trim("@{$attr} {$property['type']} {$name} {$property['comment']}");
            $tag = Tag::createInstance($tagLine, $phpdoc);
            $phpdoc->appendTag($tag);
        }

        ksort($this->methods);

        // foreach ($this->methods as $name => $method) {
        //     if (in_array($name, $methods)) {
        //         continue;
        //     }
        //     $arguments = implode(', ', $method['arguments']);
        //     $tagLine = "@method static {$method['type']} {$name}({$arguments})";
        //     if ($method['comment'] !== '') {
        //         $tagLine .= " {$method['comment']}";
        //     }
        //     $tag = Tag::createInstance($tagLine, $phpdoc);
        //     $phpdoc->appendTag($tag);
        // }

        // if ($this->write) {
        //     $eloquentClassNameInModel = $this->getClassNameInDestinationFile($reflection, 'Eloquent');

        //     // remove the already existing tag to prevent duplicates
        //     foreach ($phpdoc->getTagsByName('mixin') as $tag) {
        //         if ($tag->getContent() === $eloquentClassNameInModel) {
        //             $phpdoc->deleteTag($tag);
        //         }
        //     }

        //     $phpdoc->appendTag(Tag::createInstance('@mixin '.$eloquentClassNameInModel, $phpdoc));
        // }

        if ($this->phpstorm_noinspections) {
            /*
             * Facades, Eloquent API
             * @see https://www.jetbrains.com/help/phpstorm/php-fully-qualified-name-usage.html
             */
            $phpdoc->appendTag(Tag::createInstance('@noinspection PhpFullyQualifiedNameUsageInspection', $phpdoc));
            /*
             * Relations, other models in the same namespace
             * @see https://www.jetbrains.com/help/phpstorm/php-unnecessary-fully-qualified-name.html
             */
            $phpdoc->appendTag(
                Tag::createInstance('@noinspection PhpUnnecessaryFullyQualifiedNameInspection', $phpdoc)
            );
        }

        $serializer = new DocBlockSerializer();
        $docComment = $serializer->getDocComment($phpdoc);

        if ($this->write_mixin) {
            $phpdocMixin = new DocBlock($reflection, new Context($namespace));
            // remove all mixin tags prefixed with IdeHelper
            foreach ($phpdocMixin->getTagsByName('mixin') as $tag) {
                if (Str::startsWith($tag->getContent(), 'IdeHelper')) {
                    $phpdocMixin->deleteTag($tag);
                }
            }

            $mixinClassName = "IdeHelper{$classname}";
            $phpdocMixin->appendTag(Tag::createInstance("@mixin {$mixinClassName}", $phpdocMixin));
            $mixinDocComment = $serializer->getDocComment($phpdocMixin);
            // remove blank lines if there's no text
            if (!$phpdocMixin->getText()) {
                $mixinDocComment = preg_replace("/\s\*\s*\n/", '', $mixinDocComment);
            }

            foreach ($phpdoc->getTagsByName('mixin') as $tag) {
                if (Str::startsWith($tag->getContent(), 'IdeHelper')) {
                    $phpdoc->deleteTag($tag);
                }
            }
            $docComment = $serializer->getDocComment($phpdoc);
        }

        if ($this->write) {
            $modelDocComment = $this->write_mixin ? $mixinDocComment : $docComment;
            $filename = $reflection->getFileName();
            $contents = $this->files->get($filename);
            if ($originalDoc) {
                $contents = str_replace($originalDoc, $modelDocComment, $contents);
            } else {
                $replace = "{$modelDocComment}\n";
                $pos = strpos($contents, "final class {$classname}") ?: strpos($contents, "class {$classname}");
                if ($pos !== false) {
                    $contents = substr_replace($contents, $replace, $pos, 0);
                }
            }
            if ($this->files->put($filename, $contents)) {
                $this->info('Written new phpDocBlock to '.$filename);
            }
        }

        // FIX to make linter pass
        $mixinClassName = '';
        $classname = $this->write_mixin ? $mixinClassName : $classname;

        $allowDynamicAttributes = $this->write_mixin ? "#[\AllowDynamicProperties]\n\t" : '';
        $output = "namespace {$namespace}{\n{$docComment}\n\t{$allowDynamicAttributes}{$keyword}class {$classname} ";

        if (!$this->write_mixin) {
            $output .= "extends \Eloquent ";

            if ($interfaceNames) {
                $interfaces = implode(', \\', $interfaceNames);
                $output .= "implements \\{$interfaces} ";
            }
        }

        return $output."{}\n}\n\n";
    }

    /**
     * Load the properties from the database table.
     *
     * @param Model $model
     */
    // @phpstan-ignore missingType.return
    public function getPropertiesFromTable($model) {
        $table = $model->getTable();
        $schema = $model->getConnection()->getSchemaBuilder();
        $columns = $schema->getColumns($table);
        $driverName = $model->getConnection()->getDriverName();

        if (!$columns) {
            return;
        }

        $this->setForeignKeys($schema, $table);
        foreach ($columns as $column) {
            $name = $column['name'];
            if (in_array($name, $model->getDates())) {
                $type = $this->dateClass;
            } else {
                // Match types to php equivalent
                $type = match ($column['type_name']) {
                    // remove `bit` and 'tinyint' from here, as they represent `bool`
                    // in our code base
                    'integer', 'int', 'int4',
                    'smallint', 'int2',
                    'mediumint',
                    'bigint', 'int8' => 'int',

                    // add `bit` and 'tinyint' here
                    'tinyint', 'bit', 'boolean', 'bool' => 'bool',

                    'float', 'real', 'float4',
                    'double', 'float8' => 'float',

                    // "cast" decimals to `float`
                    'decimal' => 'float',

                    'date', 'datetime' => $this->dateClass,

                    default => 'string',
                };
            }

            if ($column['nullable']) {
                $this->nullableColumns[$name] = true;
            }
            $this->setProperty(
                $name,
                $this->getTypeInModel($model, $type),
                true,
                true,
                $column['comment'],
                $column['nullable']
            );
            if ($this->write_model_magic_where) {
                $builderClass = $this->write_model_external_builder_methods
                    ? get_class($model->newModelQuery())
                    : '\Illuminate\Database\Eloquent\Builder';

                $this->setMethod(
                    Str::camel('where_'.$name),
                    $this->getClassNameInDestinationFile($model, $builderClass)
                    .'<static>|'
                    .$this->getClassNameInDestinationFile($model, get_class($model)),
                    ['$value']
                );
            }
        }
    }
}
