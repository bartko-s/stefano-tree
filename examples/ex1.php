<?php
/**
 * Quick end dirty solution only for demonstration purpose.
 */
declare(strict_types=1);

use StefanoTree\Exception\ExceptionInterface;
use StefanoTree\TreeInterface;

session_start();

include_once __DIR__.'/../vendor/autoload.php';

$config = include_once __DIR__.'/config.php';

$dbAdapter = \Doctrine\DBAL\DriverManager::getConnection(
    $config['dbConnection'],
    new \Doctrine\DBAL\Configuration()
);

/**************************************
 *    Tree Adapter
 **************************************/
$treeAdapter = \StefanoTree\NestedSet::factory(
    new \StefanoTree\NestedSet\Options(array(
        'tableName' => 'categories',
        'idColumnName' => 'id',
        'sequenceName' => 'categories_id_seq',
        'scopeColumnName' => 'group_id',
    )),
    $dbAdapter
);

class Service
{
    private $treeAdapter;

    public function __construct(TreeInterface $treeAdapter)
    {
        $this->treeAdapter = $treeAdapter;
    }

    private function getTreeAdapter(): TreeInterface
    {
        return $this->treeAdapter;
    }

    public function createRoot(array $data): void
    {
        $errors = array();

        $label = $data['label'] ?? '';
        if (0 == strlen($label)) {
            $errors[] = 'Root Name cannot be empty.';
        } elseif (15 < strlen($label)) {
            $errors[] = 'Root Name is too long. Max length is 15 characters.';
        }

        $scope = $data['scope'] ?? '';
        if (0 === strlen($scope)) {
            $errors[] = 'Scope Name cannot be empty.';
        } elseif (!preg_match('|^[1-9][0-9]*$|', $scope)) {
            $errors[] = 'Scope Name must be integer.';
        } elseif (15 < strlen($scope)) {
            $errors[] = 'Scope Name is too long. Max length is 15 characters.';
        }

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        $data = array(
            'name' => $label,
        );

        try {
            $this->getTreeAdapter()
                ->createRootNode($data, $scope);
        } catch (ExceptionInterface $e) {
            throw new ValidationError([$e->getMessage()]);
        }
    }

    public function createNode(array $data): void
    {
        $errors = array();

        $targetId = $_POST['target_node_id'] ?? '';
        if (0 === strlen($targetId)) {
            $errors[] = 'Target Node cannot be empty.';
        }

        $label = $data['label'] ?? '';
        if (0 == strlen($label)) {
            $errors[] = 'Name cannot be empty.';
        } elseif (15 < strlen($label)) {
            $errors[] = 'Name is too long. Max length is 15 characters.';
        }

        $placement = $_POST['placement'] ?? '';
        if (0 == strlen($placement)) {
            $errors[] = 'Placement cannot be empty.';
        }

        $data = array(
            'name' => $label,
        );

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        try {
            $this->getTreeAdapter()
                ->addNode($targetId, $data, $placement);
        } catch (ExceptionInterface $e) {
            throw new ValidationError([$e->getMessage()]);
        }
    }

    public function deleteNode(array $data): void
    {
        $errors = array();

        $id = $data['id'] ?? '';

        if (0 == strlen($id)) {
            $errors[] = 'Id is missing. Cannot perform delete operation.';
        }

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        $this->getTreeAdapter()
            ->deleteBranch($id);
    }

    public function updateNode(array $data): void
    {
        $errors = array();

        $nodeId = $_POST['node_id'] ?? '';
        if (0 === strlen($nodeId)) {
            $errors[] = 'Node cannot be empty.';
        }

        $label = $data['label'] ?? '';
        if (0 == strlen($label)) {
            $errors[] = 'Name cannot be empty.';
        } elseif (15 < strlen($label)) {
            $errors[] = 'Name is too long. Max length is 15 characters.';
        }

        $data = array(
            'name' => $label,
        );

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        try {
            $this->getTreeAdapter()
                ->updateNode($nodeId, $data);
        } catch (ExceptionInterface $e) {
            throw new ValidationError([$e->getMessage()]);
        }
    }

    public function moveNode(array $data): void
    {
        $errors = array();

        $sourceId = $data['source_node_id'] ?? '';
        if (0 == strlen($sourceId)) {
            $errors[] = 'Source Node cannot be empty.';
        }

        $targetId = $data['target_node_id'] ?? '';
        if (0 == strlen($targetId)) {
            $errors[] = 'Target Node cannot be empty.';
        }

        $placement = $_POST['placement'] ?? '';
        if (0 == strlen($placement)) {
            $errors[] = 'Placement cannot be empty.';
        }

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        try {
            $this->getTreeAdapter()
                ->moveNode($sourceId, $targetId, $placement);
        } catch (ExceptionInterface $e) {
            throw new ValidationError([$e->getMessage()]);
        }
    }

    public function getRoots(): array
    {
        return $this->getTreeAdapter()
                    ->getRoots();
    }

    public function getDescendants($nodeId): array
    {
        return $this->getTreeAdapter()
            ->getDescendants($nodeId);
    }
}

class ViewHelper
{
    public function escape($string): string
    {
        return htmlspecialchars((string) $string);
    }

    public function renderTree(array $nodes): string
    {
        $html = '';

        $previousLevel = -1;
        foreach ($nodes as $node) {
            if ($previousLevel > $node['level']) {
                for ($i = $node['level']; $previousLevel > $i; ++$i) {
                    $html = $html.'</li></ul>';
                }
                $html = $html.'</li>';
            } elseif ($previousLevel < $node['level']) {
                $html = $html.'<ul>';
            } else {
                $html = $html.'</li>';
            }

            $html = $html.'<li>';
            $html = $html.'<span>'.$this->escape($node['name']).'</span>'
                .' <a href="/?action=delete&id='.$this->escape($node['id']).'" class="badge badge-danger">Delete</a>';

            $previousLevel = $node['level'];
        }

        for ($i = -1; $previousLevel > $i; ++$i) {
            $html = $html.'</li></ul>';
        }

        return $html;
    }

    public function renderSelectOptions(array $nodes): string
    {
        $pathCache = array();

        $html = '';

        foreach ($nodes as $node) {
            if (!$node['parent_id']) {
                $pathCache[$node['id']] = '/'.$node['name'];
            } else {
                $pathCache[$node['id']] = $pathCache[$node['parent_id']].'/'.$node['name'];
            }
            $html .= '<option value="'.$this->escape($node['id']).'">'.$this->escape($pathCache[$node['id']]).'</option>';
        }

        return '<option value="">---</option>'.$html;
    }

    public function renderPlacementOptions(): string
    {
        $placements = array(
            TreeInterface::PLACEMENT_CHILD_TOP => 'Child Top',
            TreeInterface::PLACEMENT_CHILD_BOTTOM => 'Child Bottom',
            TreeInterface::PLACEMENT_TOP => 'Top',
            TreeInterface::PLACEMENT_BOTTOM => 'Bottom',
        );

        $html = '';

        foreach ($placements as $id => $placement) {
            $html .= '<option value="'.$this->escape($id).'">'.$this->escape($placement).'</option>';
        }

        return '<option value="">---</option>'.$html;
    }

    public function renderErrorMessages(array $errors): string
    {
        $html = '';

        foreach ($errors as $error) {
            $html .= '<li>'.$this->escape($error).'</li>';
        }

        return '<div class="alert alert-danger"><ul class="error-container">'.$html.'</ul></div>';
    }

    public function renderSuccessMessage(string $message): string
    {
        return '<div class="alert alert-success">'.$this->escape($message).'</div>';
    }

    public function renderFlashMessage(): string
    {
        $message = $_SESSION['flashMessage'] ?? '';

        if ($message) {
            unset($_SESSION['flashMessage']);

            return $this->renderSuccessMessage($message);
        } else {
            return '';
        }
    }
}

class ValidationError extends \Exception
{
    private $errorMessages = array();

    public function __construct(array $errorMessages = array())
    {
        $this->errorMessages = $errorMessages;
    }

    public function addError(string $error): void
    {
        $this->errorMessages[] = $error;
    }

    public function addErrors(array $errors): void
    {
        $this->errorMessages = array_merge(array_values($errors), $this->errorMessages);
    }

    public function getErrors(): array
    {
        return $this->errorMessages;
    }
}

function setFlashMessageAndRedirect(string $message, string $url)
{
    $_SESSION['flashMessage'] = $message;
    $redirectUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')."://$_SERVER[HTTP_HOST]$url";
    header(sprintf('Location: %s', $redirectUrl));
    die();
}

/************************************
 *    Router
 ***********************************/
$service = new Service($treeAdapter);

try {
    switch ($_GET['action'] ?? '') {
        case 'create-scope':
            $service->createRoot($_POST);
            setFlashMessageAndRedirect('New root node and scope was successfully created.', '/');
            break;
        case 'create-node':
            $service->createNode($_POST);
            setFlashMessageAndRedirect('New node was successfully created.', '/');
            break;
        case 'move-node':
            $service->moveNode($_POST);
            setFlashMessageAndRedirect('Branch/Node was successfully moved.', '/');
            break;
        case 'update-node':
            $service->updateNode($_POST);
            setFlashMessageAndRedirect('Node was successfully updated.', '/');
            break;
        case 'delete':
            $service->deleteNode($_GET);
            setFlashMessageAndRedirect('Branch/Node was successfully deleted.', '/');
            break;
    }
} catch (ValidationError $e) {
    $errorMessage = $e->getErrors();
}

/************************************
 *   View
 ***********************************/
$wh = new ViewHelper();
?>

<html>
    <head>
        <title>Demo</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
        <style>
            .error-container {
                margin-bottom: 0;
                padding-left: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <h1><a href="/">Demo</a></h1>

            <?php
            if ($errorMessage ?? false) {
                echo $wh->renderErrorMessages($errorMessage);
            }

            echo $wh->renderFlashMessage();
            ?>

            <div class="row">
                <div class="col-sm-4">
                    <form action="/?action=create-scope" method="post">
                        <div class="form-group">
                            <label>Root name</label>
                            <input type="text" name="label" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label>Scope name</label>
                            <input type="text" name="scope" class="form-control" />
                        </div>
                        <input type="submit" value="Create" class="btn btn-primary" />
                    </form>
                </div>
            </div>
            <?php
            foreach ($service->getRoots() as $root) {
                $nodes = $service->getDescendants($root['id']); ?>
                <hr />
                <h2>Scope - <?php echo $wh->escape($root['group_id']); ?></h2>

                <div class="row">
                    <div class="col-sm-4">
                        <h3>Create</h3>
                        <form action="/?action=create-node" method="post">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="label" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Target Node</label>
                                <select name="target_node_id" class="form-control"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                            </div>
                            <div class="form-group">
                                <label>Placement</label>
                                <select name="placement" class="form-control"><?php echo $wh->renderPlacementOptions(); ?></select>
                            </div>
                            <div class="form-group">
                                <input type="submit" value="Create" class="btn btn-primary" />
                            </div>
                        </form>
                    </div>

                    <div class="col-sm-4">
                        <h3>Move</h3>
                        <form action="/?action=move-node" method="post">
                            <div class="form-group">
                                <label>Source Node</label>
                                <select name="source_node_id" class="form-control"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                            </div>
                            <div class="form-group">
                                <label>Target Node</label>
                                <select name="target_node_id" class="form-control"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                            </div>
                            <div class="form-group">
                                <label>Placement</label>
                                <select name="placement" class="form-control"><?php echo $wh->renderPlacementOptions(); ?></select>
                            </div>
                            <input type="submit" value="Move" class="btn btn-primary" />
                        </form>
                    </div>

                    <div class="col-sm-4">
                        <h3>Update</h3>
                        <form action="/?action=update-node" method="post">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="label" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label>Node</label>
                                <select name="node_id" class="form-control"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                            </div>
                            <input type="submit" value="Update" class="btn btn-primary" />
                        </form>
                    </div>
                </div>
                <?php echo $wh->renderTree($nodes); ?>
            <?php
            }?>
        </div>
    </body>
</html>