<?php

namespace HandmadeWeb\Buildamic\Fieldtypes;

use HandmadeWeb\Buildamic\BuildamicRenderer;
use HandmadeWeb\Buildamic\Fields\Field;
use HandmadeWeb\Buildamic\Fields\Fields;

class Buildamic extends BuildamicBase
{
    protected static $handle = 'buildamic';
    protected $selectable = true;
    protected $defaultValue = [];

    public function fields()
    {
        return new Fields($this->config('fields'), $this->field()->parent(), $this->field());
    }

    public function set(string $setHandle)
    {
        return new Fields($this->config("sets.{$setHandle}.fields"), $this->field()->parent(), $this->field());
    }

    protected function configFieldItems(): array
    {
        return [
            'container_id' => [
                'display' => __('Container ID'),
                'instructions' => __('Enter CSS ID that you want to apply to the container.'),
                'type' => 'text',
                'width' => 50,
            ],
            'container_class' => [
                'display' => __('Container Class'),
                'instructions' => __('Enter any CSS classes that you want to apply to the container.'),
                'type' => 'text',
                'width' => 50,
            ],
            'globals' => [
                'display' => __('Globals'),
                'instructions' => __(''),
                'type' => 'collections',
            ],
            'fields' => [
                'display' => __('Fields'),
                'type' => 'fields',
            ],
            'sets' => [
                'display' => __('Sets'),
                'type' => 'sets',
            ],
        ];
    }

    /**
     * Load data into meta.
     *
     * @param mixed $data
     *
     * @return array
     */
    public function preload()
    {
        $instance = $this;

        return [
            'fields' => $this->fields()->all()->map(function ($field) {
                return [
                    'handle' => $field->handle(),
                    'meta' => $field->meta(),
                    'value' => $field->fieldtype()->preProcess($field->defaultValue()),
                    'config' => $field->toPublishArray(),
                ];
            })->toArray(),
            'sets' => collect($this->config('sets'))->map(function ($set, $handle) use ($instance) {
                $fields = [
                    'handle' => $handle,
                    'display' => $set['display'],
                    'fields' => [],
                ];

                foreach ($instance->set($handle)->all() as $field) {
                    $fields['fields'][] = [
                        'handle' => $field->handle(),
                        'meta' => $field->meta(),
                        'value' => $field->fieldtype()->preProcess($field->defaultValue()),
                        'config' => $field->toPublishArray(),
                    ];
                }

                return !empty($fields['fields']) ? $fields : null;
            })->filter()->toArray(),
        ];
    }

    /**
     * $preProcess = true: Pre-process the data before it gets sent to the publish page.
     * $preProcess = false: Process the data before it gets saved.
     *
     * @param mixed $data
     *
     * @return array
     */
    protected function processData($data, bool $preProcess = false)
    {
        $method = $preProcess ? 'preProcess' : 'process';

        return collect($data)->map(function ($section) use ($method) {
            if ('preProcess' === $method) {
                if ('global-section' === $section['type']) {
                    $field = new Field('global', [
                        'type' => 'entries',
                        'collections' => $this->field()->get('globals'),
                    ]);

                    $field->setValue($section['value'] ?? $field->fieldtype()->defaultValue());

                    $section['computed'] = [
                        'meta' => $field->meta(),
                        'config' => $field->toPublishArray(),
                    ];
                }
            } else {
                unset($section['computed']);
                unset($section['meta']);
            }

            $section['value'] = (new Field($section['uuid'], []))
                ->setConfig(array_merge(['type' => "buildamic-{$section['type']}"], $section['config']))
                ->setBuildamicSettings($section['config']['buildamic_settings'] ?? [])
                ->setParent($this->field()->parent())
                ->setParentField($this->field())
                ->setValue($section['value'])
                ->{$method}()
                ->value() ?? [];

            return $section;
        })->toArray();
    }

    protected function performAugmentation($value, $shallow = false)
    {
        $parent = $this;

        $method = $shallow ? 'shallowAugment' : 'augment';

        $value = collect($value)->map(function ($section) use ($parent, $method) {
            if (isset($field['config']['buildamic_settings']['enabled']) && !$field['config']['buildamic_settings']['enabled']) {
                return;
            }

            if (!in_array($section['type'], ['section', 'global-section'])) {
                return;
            }

            return (new Field($section['uuid'], []))
                ->setConfig(array_merge(['type' => "buildamic-{$section['type']}"], $section['config']))
                ->setBuildamicSettings($section['config']['buildamic_settings'] ?? [])
                ->setParent($parent->field()->parent())
                ->setParentField($parent->field())
                ->setValue($section['value'])
                ->{$method}();
        })->filter()->all();

        $this->field()->setValue($value);

        return new BuildamicRenderer($this, $shallow);
    }
}
