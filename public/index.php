<?php

$di = new \Phalcon\DI\FactoryDefault();

$di->set('db', function(){
    return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        "host" => "mariadb",
        "username" => "root",
        "password" => "123456",
        "dbname" => "operand_iscool"
    ));
});

$app = new \Phalcon\Mvc\Micro($di);

//Retrieves all bank accounts
$app->get('/v1/bankaccounts', function() use ($app) {

    $sql = "SELECT id,name,balance FROM bank_account ORDER BY name";
    $result = $app->db->query($sql);
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
    $data = array();
    while ($bankAccount = $result->fetch()) {
        $data[] = array(
            'id' => $bankAccount->id,
            'name' => $bankAccount->name,
            'balance' => $bankAccount->balance,
        );
    }

    $response = new Phalcon\Http\Response();

    if ($data == false) {
        $response->setStatusCode(404, "Not Found");
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {
        $response->setJsonContent(array(
            'status' => 'FOUND',
            'data' => $data
        ));
    }

    return $response;

});

$app->post('/v1/bankaccounts', function() use ($app) {

    $bankAccount = $app->request->getPost();
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->insert("bank_account",
            array($bankAccount['name'], $bankAccount['balance']),
            array("name", "balance")
        );

        $response->setStatusCode(201, "Created");
        $bankAccount['id'] = $app->db->lastInsertId();
        $response->setJsonContent(array('status' => 'OK', 'data' => $bankAccount));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});

$app->put('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {

    $bankAccount = $app->request->getPut();
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->update("bank_account",
            array("name", "balance"),
            array($bankAccount['name'], $bankAccount['balance']),
            "id = $id"
        );

        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});

$app->delete('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->delete("bank_account",
            "id = $id"
        );

        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;
});

$app->get('/v1/bankaccounts/search/{id:[0-9]+}', function ($id) use ($app)){
	$sql = "SELECT id,name,balance FROM bank_account WHERE id = ?";
	$result = $app->db->query($sql, array($id));
	$result->setFetchMode(Phalcon\Db::FETCH_OBJ);

	$data = array();
	$bankAccount = $result->fetch();
	$response = new Phalcon\Http\Response();

	if ($bankAccount == false){
		$response->setStatusCode(404, 'Not Found');
		$response->setJsonContent(array('status' => 'NOT-FOUND'));
	} else {
		$sqlOperations = "SELECT id, operation, bank_account_id, date, value
			FROM bank_account_operations
			WHERE bank_account_id = ". $id. "
			ORDER BY date";

		$resultOperations = $app->db->query($sqlOperations);
		$resultOperations->setFetchMode(Phalcon\Db::FETCH_OBJ);
		$bankAccountOperations  = $resultOperations->fetchAll();

		$response->setJsonContent(array(
			'id' => $bankAccount->id,
			'name' => $bankAccount->name,
			'balance' => $bankAccount->balance,
			'operations' => $bankAccountOperations,
		));
		return $response;
	}
}

$app->handle();