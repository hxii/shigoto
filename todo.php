<?php

/* 
    仕事 - Shigoto

    Minimalistic todo.txt manager and parser

    made by hxii - https://paulglushak.com

*/
class Shigoto
{

    private $_todo_parsed;
    private $_todo_file;
    private $_todo_done;
    private $_todo_raw;

    public function __construct(string $todo_file, string $done_file)
    {
        $this->_todo_raw = '';
        $this->_todo_parsed = [];
        $this->_todo_file = $todo_file;
        $this->_todo_done = $done_file;
    }

    public function parse()
    {
        $this->_todo_parsed = [];
        $this->_todo_raw = '';
        if (!file_exists($this->_todo_file)) $fh = fopen($this->_todo_file, 'w');
        $fh = fopen($this->_todo_file, 'r');
        $counter = 0;
        while (($line = fgets($fh)) !== false) {
            $this->_todo_raw .= $line;
            $counter++;
            preg_match('/(?:[xX]\s(\d{4}-\d{2}-\d{2})\s)/', $line, $completed);
            if (!empty($completed)) {
                $this->_todo_parsed[$counter]['completed'] = $completed[1];
                $line = str_replace($completed[0], '', $line);
            }
            preg_match('/(?:due:(\d{4}-\d{2}-\d{2}))/', $line, $due);
            if (!empty($due)) {
                $this->_todo_parsed[$counter]['due'] = $due[1];
                $line = str_replace($due[0], '', $line);
            }
            preg_match('/(?:track:([0-9.]+))/', $line, $track);
            if (!empty($track)) {
                $this->_todo_parsed[$counter]['track'] = $track[1];
                $line = str_replace($track[0], '', $line);
            }
            preg_match('/(?:total:([0-9.]+))/', $line, $total);
            if (!empty($total)) {
                $this->_todo_parsed[$counter]['total'] = $total[1];
                $line = str_replace($total[0], '', $line);
            }
            preg_match('/(\d{4}-\d{2}-\d{2}\s)/', $line, $created);
            if (!empty($created)) {
                $this->_todo_parsed[$counter]['created'] = $created[1];
                $line = str_replace($created[0], '', $line);
            }
            preg_match('/(?:\(([A-Z])\))/', $line, $priority);
            if (!empty($priority)) {
                $this->_todo_parsed[$counter]['priority'] = $priority[1];
                $line = str_replace($priority[0], '', $line);
            }
            preg_match('/(?:\+(\w+))/', $line, $project);
            if (!empty($project)) {
                $this->_todo_parsed[$counter]['project'] = $project[1];
                $line = str_replace($project[0], '', $line);
            }
            preg_match('/(?:@(\w+))/', $line, $context);
            if (!empty($context)) {
                $this->_todo_parsed[$counter]['context'] = $context[1];
                $line = str_replace($context[0], '', $line);
            }
            $this->_todo_parsed[$counter]['task'] = trim($line);
            $this->sort();
        }
    }

    public function get_projects()
    {
        $projects = [];
        foreach ($this->_todo_parsed as $task) {
            if (isset($task['project']) && !array_key_exists($task['project'], $projects)) {
                $projects[$task['project']] = $task['project'];
            }
        }
        return $projects;
    }

    public function get_contexts()
    {
        $contexts = [];
        foreach ($this->_todo_parsed as $task) {
            if (isset($task['context']) && !array_key_exists($task['context'], $contexts)) {
                $contexts[$task['context']] = $task['context'];
            }
        }
        return $contexts;
    }

    public function get_priorities()
    {
        $priorities = [];
        foreach ($this->_todo_parsed as $task) {
            if (isset($task['priority']) && !array_key_exists($task['priority'], $priorities)) {
                $priorities[$task['priority']] = $task['priority'];
            }
        }
        return $priorities;
    }

    public function get_todo()
    {
        return $this->_todo_parsed;
    }

    public function get_todo_keys()
    {
        return array_keys($this->_todo_parsed);
    }

    public function get_todo_raw()
    {
        return $this->_todo_raw;
    }

    public function filter(string $key, string $value)
    {
        $results = $this->_todo_parsed;
        foreach ($results as $task => $data) {
            if (!isset($data[$key]) || $data[$key] !== $value) {
                unset($results[$task]);
            }
        }
        return array_keys($results);
    }

    public function sort(string $method = 'by_created_date', string $direction = 'desc')
    {
        if ( $method === 'by_created_date' ) {
            uasort($this->_todo_parsed, function($a, $b) {
                if (empty($a['created']) || empty($b['created'])) return -1;
                return strtotime($b['created']) <=> strtotime($a['created']);
            });
        }
    }

    public function complete(int $key)
    {
        if (!isset($this->_todo_parsed[$key]['completed'])) {
            $this->_todo_parsed[$key]['completed'] = date('Y-m-d');
        } else {
            unset($this->_todo_parsed[$key]['completed']);
        }
        $this->write_todo($this->_todo_parsed);
    }

    public function archive(int $key)
    {
        $task = $this->get_task($key);
        $fh = fopen($this->_todo_done, 'a');
        fwrite($fh, $task.PHP_EOL);
        fclose($fh);
        $this->delete($key);
    }

    public function create(string $task)
    {
        $fh = fopen($this->_todo_file, 'a');
        fwrite($fh, $task . PHP_EOL);
        fclose($fh);
    }

    public function delete(int $key)
    {
        unset($this->_todo_parsed[$key]);
        $this->write_todo($this->_todo_parsed);
    }

    public function track(int $key)
    {
        if (isset($this->_todo_parsed[$key]['track'])) {
            $track = $this->_todo_parsed[$key]['track'];
            if (!isset($this->_todo_parsed[$key]['total'])) {
                $this->_todo_parsed[$key]['total'] = 0;
            }
            $this->_todo_parsed[$key]['total'] += round(microtime(true) - $track, 2);
            unset($this->_todo_parsed[$key]['track']);
        } else {
            $this->_todo_parsed[$key]['track'] = microtime(true);  
        }
        // var_dump($this->_todo_parsed);
        $this->write_todo($this->_todo_parsed);
    }

    public function get_task(int $key, bool $format = true)
    {
        $task = $this->_todo_parsed[$key];
        if (!$format) {
            return $task;
        }
        $completed = (isset($task['completed'])) ? "X {$task['completed']} " : '';
        $created = (isset($task['created'])) ? "{$task['created']} " : '';
        $priority = (isset($task['priority'])) ? "({$task['priority']}) " : '';
        $data = "{$task['task']} ";
        $project = (isset($task['project'])) ? "+{$task['project']} " : '';
        $context = (isset($task['context'])) ? "@{$task['context']} " : '';
        $due = (isset($task['due'])) ? "due:{$task['due']} " : '';
        $track = (isset($task['track'])) ? "track:{$task['track']} " : '';
        $total = (isset($task['total'])) ? "total:{$task['total']} " : '';
        return trim($completed . $created . $priority . $data . $project . $context . $due . $track . $total);
    }

    public function get_task_html(int $key, string $tag = 'li', bool $closing_tag = false)
    {
        $classes = '';
        $html = '<' . $tag . ' ';
        $task = $this->_todo_parsed[$key];
        $completed = (isset($task['completed'])) ? "X {$task['completed']} " : '';
        if ($completed) {
            $classes .= 'completed ';
        }
        $created = (isset($task['created'])) ? "{$task['created']} " : '';
        $priority = (isset($task['priority'])) ? "<span class='priority'>({$task['priority']})</span> " : '';
        $data = "{$task['task']} ";
        $project = (isset($task['project'])) ? "<a class='project' href='?project={$task['project']}'>+{$task['project']}</a> " : '';
        $context = (isset($task['context'])) ? "<a class='context' href='?context={$task['context']}'>@{$task['context']}</a> " : '';
        $due = (isset($task['due'])) ? "<span class='due'>due:{$task['due']}</span> " : '';
        $track = (isset($task['track'])) ? "<span class='g'>tracking</span> " : '';
        $total = (isset($task['total'])) ? "total:{$task['total']}s " : '';
        $html .= 'class="' . $classes . '">' . $completed . $created . $priority . $data . $project . $context . $due . $track . $total . (($closing_tag)? '</' . $tag . '>':'');
        return $html;
    }

    private function write_todo(array $todo)
    {
        $fh = fopen($this->_todo_file, 'w');
        foreach ($todo as $key => $task) {
            fwrite($fh, $this->get_task($key) . PHP_EOL);
        }
        fclose($fh);
    }

    public function get_parsed()
    {
        return $this->_todo_parsed;
    }

}