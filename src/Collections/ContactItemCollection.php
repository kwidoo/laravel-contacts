<?php

namespace Kwidoo\Contacts\Collections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Kwidoo\Contacts\Contracts\Item;
use Kwidoo\Contacts\Items\ContactItem;
use phpDocumentor\Reflection\Types\Self_;

class ContactItemCollection extends Collection
{
    protected Model $model;

    protected array $additional = [
        'company',
        'first_name',
        'last_name',
        'type',
        'title',
        'middle_name',
        'vat_id',
        'position',
        'notes',
        'properties'
    ];
    /**
     * Push one or more items onto the end of the collection.
     *
     * @param  mixed  $values
     * @return $this
     */
    public function push(...$values)
    {
        if ($values[0] instanceof Item) {
            // @todo add flag to clear previous values
            foreach ($this->items as $item) {
                if ($item->is($values[0])) {
                    return $this;
                }
            }
        }

        return parent::push($values[0]);
    }

    /**
     * Find Item by value
     *
     * @param string|Item $value
     *
     * @return Item|null
     */
    public function find($value): ?Item
    {
        $item = $this->findWithKey($value);
        if (!empty($item)) {
            return array_values($item)[0];
        }
        return null;
    }

    /**
     * @param mixed $value
     *
     * @return Item[]
     */
    public function findWithKey($value): array
    {
        if ($value instanceof Item) {
            foreach ($this->items as $key => $item) {
                if ($item->is($value)) {
                    return [$key => $item];
                }
            }
        }
        if ($this->is_uuid($value)) {
            foreach ($this->items as $key => $item) {
                if ($item->uuid === $value) {
                    return [$key => $item];
                }
            }
        }
        if (is_string($value)) {
            foreach ($this->items as $key => $item) {
                if ($item->value === $value) {
                    return [$key => $item];
                }
            }
        }
        return [];
    }

    public function pull($value, $default = null)
    {
        $item = $this->findWithKey($value);
        if (!empty($item)) {
            $key = array_keys($item)[0];
            return parent::pull($key, $default);
        }
    }

    /**
     * Get the values of a given key.
     *
     * @param  string|array $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck($value = 'value', $key = null)
    {
        $values = [];
        foreach ($this->items as $item) {
            if ($key) {
                $values[$item->$key] = $item->$value;
            }
            if ($key === null) {
                $values[] = $item->$value;
            }
        }
        return new static($values);
    }

    /**
     * @param string $uuid
     *
     * @return bool
     */
    protected function is_uuid($uuid): bool
    {
        if (!is_string($uuid)) {
            return false;
        }
        $validator = Validator::make(['uuid' => $uuid], ['uuid' => 'uuid']);
        return !$validator->fails();
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function ofType(string $type): self
    {
        return $this->filter(function (Item $item) use ($type) {
            return $item->type === $type;
        });
    }

    /**
     * @param FormRequest $request
     *
     * @return self
     */
    public function fillFrom(FormRequest $request): self
    {
        foreach ($request->validated() as $key => $value) {
            if (!in_array($key, $this->additional)) {
                $this->push(new ContactItem([
                    'type' => $key,
                    'value' => $value
                ]));
            }
            if (!in_array($key, $this->additional)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function __get($name)
    {
        if (in_array($name, config('contacts.value_types'))) {
            $value = $this->ofType($name)->pluck('value');
            if ($value->count() === 1) {
                return $value->first();
            }
            return $value;
        }
        if (in_array($name, $this->additional)) {
            return $this->model->$name;
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (in_array($name, config('contacts.value_types'))) {
            $this->push(new ContactItem(['type' => $name, 'value' => $value]));
        }
        if (in_array($name, $this->additional)) {
            $this->model->$name = $value;
        }
    }

    /**
     * @todo Find a better way to save the collection
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function save()
    {
        $this->model->values = $this->all();
        $this->model->save();
    }

    public function delete()
    {
        $this->model->values = null;
        $this->model->save();
    }
}
