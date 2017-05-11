<?php

namespace Xtools;

use Mediawiki\Api\SimpleRequest;
use Symfony\Component\DependencyInjection\Container;

class UserRepository extends Repository
{

    /**
     * Convenience method to get a new User object.
     * @param string $username
     * @return User
     */
    public static function getUser($username, Container $container)
    {
        $user = new User($username);
        $userRepo = new UserRepository();
        $userRepo->setContainer($container);
        $user->setRepository($userRepo);
        return $user;
    }

    /**
     * Get the user's ID.
     * @param string $databaseName The database to query.
     * @param string $username The username to find.
     * @return int
     */
    public function getId($databaseName, $username)
    {
        $userTable = $this->getTableName($databaseName, 'user');
        $sql = "SELECT user_id FROM $userTable WHERE user_name = :username LIMIT 1";
        $resultQuery = $this->getProjectsConnection()->prepare($sql);
        $resultQuery->bindParam("username", $username);
        $resultQuery->execute();
        $userId = (int)$resultQuery->fetchColumn();
        return $userId;
    }

    /**
     * @param Project $project
     * @param string $username
     * @return array
     */
    public function getGroups(Project $project, $username)
    {
        $api = $this->getMediawikiApi($project);
        $params = [ "list"=>"users", "ususers"=>$username, "usprop"=>"groups" ];
        $query = new SimpleRequest('query', $params);
        $result = [];
        $res = $api->getRequest($query);
        if (isset($res["batchcomplete"]) && isset($res["query"]["users"][0]["groups"])) {
            $result = $res["query"]["users"][0]["groups"];
        }
        return $result;
    }

    /**
     * @param string $username
     * @return string[]
     */
    public function getGlobalGroups($username)
    {
        // Instantiate the default project.
        $defaultProject = $this->container->getParameter('default_project');
        $project = new Project($defaultProject);
        $projectRepo = new ProjectRepository();
        $projectRepo->setContainer($this->container);
        $project->setRepository($projectRepo);

        // Create the API query.
        $api = $this->getMediawikiApi($project);
        $params = [ "meta"=>"globaluserinfo", "guiuser"=>$username, "guiprop"=>"groups" ];
        $query = new SimpleRequest('query', $params);

        // Get the result.
        $res = $api->getRequest($query);
        $result = [];
        if (isset($res["batchcomplete"]) && isset($res["query"]["globaluserinfo"]["groups"])) {
            $result = $res["query"]["globaluserinfo"]["groups"];
        }
        return $result;
    }
}