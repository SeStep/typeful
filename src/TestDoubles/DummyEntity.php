<?php declare(strict_types=1);

namespace SeStep\Typeful\TestDoubles;

/**
 * @property string|null $os
 * @property int|null $version
 * @property string|null $previewImage
 */
class DummyEntity
{
    private $data = [];

    private $assignGroup = 'construct';
    private $assignGroups = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
        if (!isset($this->assignGroups[$this->assignGroup])) {
            $this->assignGroups[$this->assignGroup] = [];
        }
        $this->assignGroups[$this->assignGroup][] = [$name, $value];
    }

    public function __get($name)
    {
        if (!isset($this->$name)) {
            throw new \InvalidArgumentException();
        }

        return $this->data[$name];
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->data);
    }

    public function setAssignGroup(string $group)
    {
        $this->assignGroup = $group;
    }

    public function getAssigns(string $group = null)
    {
        return $this->assignGroups[$group ?: $this->assignGroup] ?? [];
    }

    public function getData(): array
    {
        return $this->data;
    }
}
