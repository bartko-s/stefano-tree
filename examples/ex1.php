<?php
/**
 * Quick end dirty solution only for demonstration purpose.
 */
declare(strict_types=1);

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet;
use StefanoTree\TreeInterface;

session_start();

include_once __DIR__ . '/../vendor/autoload.php';

$config = include_once __DIR__ . '/config.php';

$dbAdapter = DriverManager::getConnection(
        $config['dbConnection'],
        new Configuration()
);

/**************************************
 *    Config
 ***************************************/
$options = array(
        'tableName' => 'categories',
        'idColumnName' => 'id',

        'scopeColumnName' => 'group_id',
);

/**************************************
 *    Tree Adapter
 **************************************/
$treeAdapter = new NestedSet($options, $dbAdapter);

/***************************************
 * Join example 1
 ***************************************/
/*
$options['dbSelectBuilder'] = function () {
    return 'SELECT categories.*, m.name AS metaName '
        .' FROM categories'
        .' LEFT JOIN metadata AS m ON m.id=categories.id';
};

$treeAdapter = new \StefanoTree\NestedSet($options, $dbAdapter);
*/

/***************************************
 * Join example 2
 ***************************************/
/*
class SelectBuilder
{
    public function build() {
        return 'SELECT categories.*, m.name AS metaName '
            . ' FROM categories'
            . ' LEFT JOIN metadata AS m ON m.id=categories.id';
    }
}

$options['dbSelectBuilder'] = array(new SelectBuilder(), 'build');
$treeAdapter = new \StefanoTree\NestedSet($options, $dbAdapter);
*/

class Service
{
    public function __construct(
            private readonly TreeInterface $treeAdapter,
    )
    {
    }

    private function getTreeAdapter(): TreeInterface
    {
        return $this->treeAdapter;
    }

    /**
     * @param array<string, mixed> $data
     */
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
        } catch (ValidationException $e) {
            throw new ValidationError(array($e->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
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

        $placement = $data['placement'] ?? '';
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
        } catch (ValidationException $e) {
            throw new ValidationError(array($e->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @param array<string, mixed> $data
     */
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
        } catch (ValidationException $e) {
            throw new ValidationError(array($e->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
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

        $placement = $data['placement'] ?? '';
        if (0 == strlen($placement)) {
            $errors[] = 'Placement cannot be empty.';
        }

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        try {
            $this->getTreeAdapter()
                    ->moveNode($sourceId, $targetId, $placement);
        } catch (ValidationException $e) {
            throw new ValidationError(array($e->getMessage()));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoots(): array
    {
        return $this->getTreeAdapter()
                ->getRoots();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDescendants(int|string $nodeId): array
    {
        return $this->getTreeAdapter()
                ->getDescendantsQueryBuilder()
                ->get($nodeId);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDescendants(array $criteria): array
    {
        $queryBuilder = $this->getTreeAdapter()
                ->getDescendantsQueryBuilder();

        $errors = array();

        $nodeId = $criteria['node_id'] ?? '';
        if (0 === strlen($nodeId)) {
            $errors[] = 'Node cannot be empty.';
        }

        $excludeFirstNLevel = $criteria['exclude_first_n_level'] ?? null;
        if (null !== $excludeFirstNLevel) {
            if (!preg_match('|^[0-9]*$|', $excludeFirstNLevel)) {
                $errors[] = 'Exclude First N Level  must be positive integer,';
            } else {
                $queryBuilder->excludeFirstNLevel((int)$excludeFirstNLevel);
            }
        }

        $levelLimit = $criteria['level_limit'] ?? null;
        if (null !== $levelLimit) {
            if (!preg_match('|^[0-9]*$|', $levelLimit)) {
                $errors[] = 'Level limit must be positive integer,';
            } else {
                $queryBuilder->levelLimit((int)$levelLimit);
            }
        }

        $excludeBranch = $criteria['exclude_node_id'] ?? null;
        if (null !== $excludeBranch) {
            $queryBuilder->excludeBranch($excludeBranch);
        }

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        return $queryBuilder->get($nodeId);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAncestors(array $criteria): array
    {
        $queryBuilder = $this->getTreeAdapter()
                ->getAncestorsQueryBuilder();

        $errors = array();

        $nodeId = $criteria['node_id'] ?? '';
        if (0 === strlen($nodeId)) {
            $errors[] = 'Node cannot be empty.';
        }

        $excludeFirstNLevel = $criteria['exclude_first_n_level'] ?? null;
        if (null !== $excludeFirstNLevel) {
            if (!preg_match('|^[0-9]*$|', $excludeFirstNLevel)) {
                $errors[] = 'Exclude First N Level  must be positive integer,';
            } else {
                $queryBuilder->excludeFirstNLevel((int)$excludeFirstNLevel);
            }
        }

        $excludeLastNLevel = $criteria['exclude_last_n_level'] ?? null;
        if (null !== $excludeLastNLevel) {
            if (!preg_match('|^[0-9]*$|', $excludeLastNLevel)) {
                $errors[] = 'Exclude Last N Level  must be positive integer,';
            } else {
                $queryBuilder->excludeLastNLevel((int)$excludeLastNLevel);
            }
        }

        if (count($errors)) {
            throw new ValidationError($errors);
        }

        return $queryBuilder->get($nodeId);
    }
}

class ViewHelper
{
    public function escape(mixed $string): string
    {
        return htmlspecialchars((string)$string);
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function renderTree(array $nodes): string
    {
        $html = '';

        $previousLevel = -1;
        foreach ($nodes as $node) {
            if ($previousLevel > $node['level']) {
                for ($i = $node['level']; $previousLevel > $i; ++$i) {
                    $html = $html . '</li></ul>';
                }
                $html = $html . '</li>';
            } elseif ($previousLevel < $node['level']) {
                $html = $html . '<ul>';
            } else {
                $html = $html . '</li>';
            }

            $html = $html . '<li>';
            $html = $html . '<span>' . $this->escape($node['name']) . '</span>'
                    . ' <a href="/?action=delete&id=' . $this->escape($node['id']) . '" class="badge bg-danger">Delete</a>';

            $previousLevel = $node['level'];
        }

        for ($i = -1; $previousLevel > $i; ++$i) {
            $html = $html . '</li></ul>';
        }

        return $html;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function renderBreadcrumbs(array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            $html .= '<li class="breadcrumb-item"><a href="#">' . $this->escape($node['name']) . '</a></li>';
        }

        return '<ol class="breadcrumb">' . $html . '</ol>';
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function renderSelectOptions(array $nodes): string
    {
        $pathCache = array();

        $html = '';

        foreach ($nodes as $node) {
            if (!$node['parent_id']) {
                $pathCache[$node['id']] = '/' . $node['name'];
            } else {
                $pathCache[$node['id']] = $pathCache[$node['parent_id']] . '/' . $node['name'];
            }
            $html .= '<option value="' . $this->escape($node['id']) . '">' . $this->escape($pathCache[$node['id']]) . '</option>';
        }

        return '<option value="">---</option>' . $html;
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
            $html .= '<option value="' . $this->escape($id) . '">' . $this->escape($placement) . '</option>';
        }

        return '<option value="">---</option>' . $html;
    }

    /**
     * @param array<int, string> $errors
     */
    public function renderErrorMessages(array $errors): string
    {
        $html = '';

        foreach ($errors as $error) {
            $html .= '<li>' . $this->escape($error) . '</li>';
        }

        return '<div class="alert alert-danger"><ul class="error-container">' . $html . '</ul></div>';
    }

    public function renderSuccessMessage(string $message): string
    {
        return '<div class="alert alert-success">' . $this->escape($message) . '</div>';
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

class ValidationError extends Exception
{
    /**
     * @param array<int, string> $errorMessages
     */
    public function __construct(private array $errorMessages = array())
    {
    }

    public function addError(string $error): void
    {
        $this->errorMessages[] = $error;
    }

    /**
     * @param array<int, string> $errors
     */
    public function addErrors(array $errors): void
    {
        $this->errorMessages = array_merge(array_values($errors), $this->errorMessages);
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errorMessages;
    }
}

function setFlashMessageAndRedirect(string $message, string $url): void
{
    $_SESSION['flashMessage'] = $message;
    $redirectUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$url}";
    header(sprintf('Location: %s', $redirectUrl));

    exit;
}

/************************************
 *    Router
 ***********************************/
$service = new Service($treeAdapter);

$descendants = array();
$ancestors = array();

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

        case 'descendant-test':
            $descendants = $service->findDescendants($_GET);
            $showDescendantTestBlock = true;

            break;

        case 'ancestor-test':
            $ancestors = $service->findAncestors($_GET);
            $showAncestorTestBlock = true;

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
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
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
        <div class="col-12 col-md-6 col-lg-4">
            <form action="/?action=create-scope" method="post">
                <div class="mb-3">
                    <label for="root-label">Root name</label>
                    <input type="text" id="root-label" name="label" class="form-control"/>
                </div>
                <div class="mb-3">
                    <label for="root-scope">Scope name</label>
                    <input type="text" id="root-scope" name="scope" class="form-control"/>
                </div>
                <input type="submit" value="Create" class="btn btn-primary"/>
            </form>
        </div>
    </div>
    <?php
    foreach ($service->getRoots() as $root) {
        $nodes = $service->getDescendants($root['id']);
        $sid = $wh->escape($root['group_id']); ?>
        <hr/>
        <h2>Scope - <?php echo $wh->escape($root['group_id']); ?></h2>

        <div class="row">
            <div class="col-12 col-md-6 col-lg-2">
                <h3>Create</h3>
                <form action="/?action=create-node" method="post">
                    <div class="mb-3">
                        <label for="create-label-<?php echo $sid; ?>">Name</label>
                        <input type="text" id="create-label-<?php echo $sid; ?>" name="label" class="form-control"/>
                    </div>
                    <div class="mb-3">
                        <label for="create-target-<?php echo $sid; ?>">Target Node</label>
                        <select id="create-target-<?php echo $sid; ?>" name="target_node_id"
                                class="form-select"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                    </div>
                    <div class="mb-3">
                        <label for="create-placement-<?php echo $sid; ?>">Placement</label>
                        <select id="create-placement-<?php echo $sid; ?>" name="placement"
                                class="form-select"><?php echo $wh->renderPlacementOptions(); ?></select>
                    </div>
                    <div class="mb-3">
                        <input type="submit" value="Create" class="btn btn-primary"/>
                    </div>
                </form>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <h3>Move</h3>
                <form action="/?action=move-node" method="post">
                    <div class="mb-3">
                        <label for="move-source-<?php echo $sid; ?>">Source Node</label>
                        <select id="move-source-<?php echo $sid; ?>" name="source_node_id"
                                class="form-select"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                    </div>
                    <div class="mb-3">
                        <label for="move-target-<?php echo $sid; ?>">Target Node</label>
                        <select id="move-target-<?php echo $sid; ?>" name="target_node_id"
                                class="form-select"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                    </div>
                    <div class="mb-3">
                        <label for="move-placement-<?php echo $sid; ?>">Placement</label>
                        <select id="move-placement-<?php echo $sid; ?>" name="placement"
                                class="form-select"><?php echo $wh->renderPlacementOptions(); ?></select>
                    </div>
                    <input type="submit" value="Move" class="btn btn-primary"/>
                </form>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <h3>Update</h3>
                <form action="/?action=update-node" method="post">
                    <div class="mb-3">
                        <label for="update-label-<?php echo $sid; ?>">Name</label>
                        <input type="text" id="update-label-<?php echo $sid; ?>" name="label" class="form-control"/>
                    </div>
                    <div class="mb-3">
                        <label for="update-node-<?php echo $sid; ?>">Node</label>
                        <select id="update-node-<?php echo $sid; ?>" name="node_id"
                                class="form-select"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                    </div>
                    <input type="submit" value="Update" class="btn btn-primary"/>
                </form>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h3>Descendant Test</h3>
                <form action="/" method="get">
                    <div class="mb-3">
                        <label for="descendant-node-<?php echo $sid; ?>">Node</label>
                        <select id="descendant-node-<?php echo $sid; ?>" name="node_id"
                                class="form-select"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                    </div>
                    <div class="mb-3">
                        <label for="descendant-exclude-first-<?php echo $sid; ?>">Exclude First N Level</label>
                        <input type="number" id="descendant-exclude-first-<?php echo $sid; ?>" min="0" step="1"
                               name="exclude_first_n_level" class="form-control"/>
                    </div>
                    <div class="mb-3">
                        <label for="descendant-level-limit-<?php echo $sid; ?>">Level Limit</label>
                        <input type="number" id="descendant-level-limit-<?php echo $sid; ?>" min="0" step="1"
                               name="level_limit" class="form-control"/>
                    </div>
                    <div class="mb-3">
                        <label for="descendant-exclude-branch-<?php echo $sid; ?>">Exclude Branch</label>
                        <select id="descendant-exclude-branch-<?php echo $sid; ?>" name="exclude_node_id"
                                class="form-select"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                    </div>
                    <input type="hidden" name="action" value="descendant-test"/>
                    <input type="hidden" name="scope" value="<?php echo $wh->escape($root['group_id']); ?>"/>
                    <input type="submit" value="Show" class="btn btn-primary"/>
                </form>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h3>Ancestor Test</h3>
                <form action="/" method="get">
                    <div class="mb-3">
                        <label for="ancestor-node-<?php echo $sid; ?>">Node</label>
                        <select id="ancestor-node-<?php echo $sid; ?>" name="node_id"
                                class="form-select"><?php echo $wh->renderSelectOptions($nodes); ?></select>
                    </div>
                    <div class="mb-3">
                        <label for="ancestor-exclude-first-<?php echo $sid; ?>">Exclude First N Level</label>
                        <input type="number" id="ancestor-exclude-first-<?php echo $sid; ?>" min="0" step="1"
                               name="exclude_first_n_level" class="form-control"/>
                    </div>
                    <div class="mb-3">
                        <label for="ancestor-exclude-last-<?php echo $sid; ?>">Exclude Last N Level</label>
                        <input type="number" id="ancestor-exclude-last-<?php echo $sid; ?>" min="0" step="1"
                               name="exclude_last_n_level" class="form-control"/>
                    </div>
                    <input type="hidden" name="action" value="ancestor-test"/>
                    <input type="hidden" name="scope" value="<?php echo $wh->escape($root['group_id']); ?>"/>
                    <input type="submit" value="Show" class="btn btn-primary"/>
                </form>
            </div>
        </div>

        <hr/>

        <div class="row">
            <div class="col-12 col-md-6">
                <h3>Whole Tree</h3>
                <?php echo $wh->renderTree($nodes); ?>
            </div>
            <div class="col-12 col-md-6">
                <?php
                if (($showDescendantTestBlock ?? false) && $root['group_id'] == $_GET['scope']) {
                    ?>
                    <h3>Descendants Test Result</h3>
                    <?php
                    if (0 == count($descendants)) {
                        echo $wh->renderErrorMessages(array('No descendants was found'));
                    } else {
                        echo $wh->renderTree($descendants);
                    } ?>
                    <?php
                } ?>

                <?php
                if (($showAncestorTestBlock ?? false) && $root['group_id'] == $_GET['scope']) {
                    ?>
                    <h3>Ancestors Test Result</h3>
                    <?php
                    if (0 == count($ancestors)) {
                        echo $wh->renderErrorMessages(array('No ancestors was found'));
                    } else {
                        echo $wh->renderBreadcrumbs($ancestors);
                        echo $wh->renderTree($ancestors);
                    } ?>
                    <?php
                } ?>
            </div>
        </div>
        <?php
    } ?>
</div>
</body>
</html>