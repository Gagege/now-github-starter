<?php

namespace Grocy\Controllers;

class ChoresApiController extends BaseApiController
{
	public function __construct(\DI\Container $container)
	{
		parent::__construct($container);
	}

	public function TrackChoreExecution(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args)
	{
		$requestBody = $request->getParsedBody();

		try
		{
			$trackedTime = date('Y-m-d H:i:s');
			if (array_key_exists('tracked_time', $requestBody) && (IsIsoDateTime($requestBody['tracked_time']) || IsIsoDate($requestBody['tracked_time'])))
			{
				$trackedTime = $requestBody['tracked_time'];
			}

			$doneBy = GROCY_USER_ID;
			if (array_key_exists('done_by', $requestBody) && !empty($requestBody['done_by']))
			{
				$doneBy = $requestBody['done_by'];
			}

			$choreExecutionId = $this->getChoresService()->TrackChore($args['choreId'], $trackedTime, $doneBy);
			return $this->ApiResponse($response, $this->getDatabase()->chores_log($choreExecutionId));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function ChoreDetails(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args)
	{
		try
		{
			return $this->ApiResponse($response, $this->getChoresService()->GetChoreDetails($args['choreId']));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function Current(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args)
	{
		return $this->ApiResponse($response, $this->getChoresService()->GetCurrent());
	}

	public function UndoChoreExecution(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args)
	{
		try
		{
			$this->ApiResponse($response, $this->getChoresService()->UndoChoreExecution($args['executionId']));
			return $this->EmptyApiResponse($response);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function CalculateNextExecutionAssignments(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args)
	{
		try
		{
			$requestBody = $request->getParsedBody();

			$choreId = null;
			if (array_key_exists('chore_id', $requestBody) && !empty($requestBody['chore_id']) && is_numeric($requestBody['chore_id']))
			{
				$choreId = intval($requestBody['chore_id']);
			}

			if ($choreId === null)
			{
				$chores = $this->getDatabase()->chores();
				foreach ($chores as $chore)
				{
					$this->getChoresService()->CalculateNextExecutionAssignment($chore->id);
				}
			}
			else
			{
				$this->getChoresService()->CalculateNextExecutionAssignment($choreId);
			}

			return $this->EmptyApiResponse($response);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
}
