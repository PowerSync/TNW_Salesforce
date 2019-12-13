<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls\Query;

class Soql
{

    /**
     * @param array
     * @return string
     */
    public function build($data)
    {
        if (empty($data['from'])) {
            throw new \RuntimeException('SOQL part "from" is Empty');
        }

        if (empty($data['columns'])) {
            throw new \RuntimeException('SOQL part "columns" is Empty');
        }

        $soqlTemplate = 'SELECT %s FROM %s';

        if (!empty($data['where'])) {
            $soqlTemplate .=' WHERE %s';
        } else {
            $data['where'] = [];
        }

        if (!empty($data['limit'])) {
            $soqlTemplate .=' LIMIT %s';
        } else {
            $data['limit'] = '';
        }

        if (!empty($data['offset'])) {
            $soqlTemplate .=' OFFSET %s';
        } else {
            $data['offset'] = '';
        }

        return sprintf(
            $soqlTemplate,
            $this->select($data['columns']),
            $data['from'],
            $this->where($data['where']),
            $data['limit'], $data['offset']
        );
    }

    /**
     * @param array $columns
     * @return string
     */
    public function select(array $columns)
    {
        return implode(', ', $columns);
    }

    /**
     * @param array $entities
     * @return string
     */
    public function where($conditions)
    {
        if (empty($conditions)) {
            return;
        }

        $groups = $this->mergeGroup($conditions);

        $this->prepareLookupWhereGroup($groups);
        return $this->generateLookupWhereGroup($groups);
    }

    /**
     * @param $entities
     * @return array
     */
    protected function mergeGroup($conditions)
    {
//        $group = [];
//        foreach ($conditions as $condition) {
//            $group['OR'][] = $condition;
//        }

        return $conditions;
    }

    /**
     * @param array $groups
     */
    protected function prepareLookupWhereGroup(array &$groups)
    {
        foreach ($groups as &$group) {
            foreach ($group as $fieldName => &$condition) {
                switch (true) {
                    case array_key_exists('=', $condition):
                        $value = $this->soqlQuote($condition['=']);
                        $condition = "$fieldName={$value}";
                        break;

                    case array_key_exists('!=', $condition):
                        $value = $this->soqlQuote($condition['!=']);
                        $condition = "$fieldName!={$value}";
                        break;

                    case array_key_exists('LIKE', $condition):
                        $value = $this->soqlQuote($condition['LIKE']);
                        $condition = "$fieldName LIKE {$value}";
                        break;

                    case array_key_exists('IN', $condition):
                        $in = is_array($condition['IN'])
                            ? implode(',', array_map(array($this, 'soqlQuote'), array_unique($condition['IN'])))
                            : $condition['IN'];

                        $condition = "$fieldName IN ({$in})";
                        break;

                    default:
                        if (is_array($condition)) {
                            $this->prepareLookupWhereGroup($condition);
                        }
                        break;
                }
            }
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function soqlQuote($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = addslashes($value);
        return "'$value'";
    }

    /**
     * @param array $groups
     * @return string
     */
    protected function generateLookupWhereGroup(array $groups)
    {
        $sql = '';
        $first = true;
        foreach ($groups as $key => $group) {
            foreach ($group as $fieldName => $condition) {
                $sql .= ($first ? '': " $key ");

                if (!is_array($condition)) {
                    $sql .= $condition;
                } else {
                    $sql .= "({$this->generateLookupWhereGroup($condition)})";
                }

                $first = false;
            }
        }

        return $sql;
    }
}