<?php

namespace SomeBundle\Controller;

use SomeBundle\Entity\Url;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SomeController extends Controller
{
    public function showAction(Request $request, $id, $name, $range = '24h')
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $someVariable = $em->getRepository('SomeBundle:Url')->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);
        if (!$someVariable) {
            throw new NotFoundHttpException();
        }

        $options = (new OptionsFactory())->createOptions($range);

        $someVariableQuery = $em->getRepository('SomeBundle:Response')->getLogsForsomeVariableQuery([
            'url' => '',
            'user' => $user,
            'date_start' => $options->getDateStart(),
            'date_stop' => $options->getDateStop(),
        ]);

        $paginator  = $this->get('knp_paginator');
        $logs = $paginator->paginate(
            $someVariableQuery,
            $request->query->getInt('page', 1),
            10
        );

        // it is raw query so we need to pass int/string arguments not object
        $chart = $em->getRepository('SomeBundle:Response')->getChartForsomeVariable([
            'date_start' => $options->getDateStart()->format('Y.m.d H:i:s'),
            'date_stop' => $options->getDateStop()->format('Y.m.d H:i:s'),
            'range_format' => $options->getRangeFormat(),
            'range_value' => $options->getRangeValue(),
        ]);

        return $this->render('SomeBundle:Some:show.html.twig', [
            'someVariable' => $someVariable,
            'logs' => $logs,
            'chart' => $chart,
        ]);
    }

    private function handleObject($object, $request)
    {
        if ($request->request->has('someVariable')) {
            $someVariable = $request->request->get('someVariable');
            $parse = parse_url($someVariable['url']);

            if (isset($parse['path'])) {
                $object->setRawDomain($parse['path']);
            }

            if (isset($parse['host'])) {
                $object->setRawDomain($parse['host']);
            }
        }

        return $object;
    }
}

class OptionsFactory
{
    protected $values = [
        '24h' => 'Daily',
        '7d' => 'Weekly',
        '1m' => 'Monthly',
        '6m' => 'Monthly',
    ];

    public function __construct()
    {
    }

    public function createOptions($value)
    {
        if (!array_key_exists($value, $this->values)) {
            throw new \InvalidArgumentException();
        }

        $className = '\\SomeBundle\\Controller\\' . $this->values[$value];
        return new $className($value);
    }
}

interface OptionsInterface
{
    public function getDateStart();

    public function getDateStop();

    public function getRangeFormat();

    public function getRangeValue();
}

class Daily implements OptionsInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getDateStart()
    {
        return (new \DateTime())->setTime(0, 0, 0);
    }

    public function getDateStop()
    {
        return (new \DateTime())->setTime(23, 59, 59);
    }

    public function getRangeFormat()
    {
        return '%H';
    }

    public function getRangeValue()
    {
        return 3600;
    }
}

class Weekly implements OptionsInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getDateStart()
    {
        return (new \DateTime())->modify('-7 days')->setTime(0, 0, 0);
    }

    public function getDateStop()
    {
        return (new \DateTime())->modify('-1 day')->setTime(23, 59, 59);
    }

    public function getRangeFormat()
    {
        return '%Y.%d.%H';
    }

    public function getRangeValue()
    {
        return 3600;
    }
}

class Monthly implements OptionsInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getDateStart()
    {
        if ($this->value == '1m') {
            return (new \DateTime())->modify('-1 month')->modify('first day of this month')->setTime(0, 0, 0);
        }

        if ($this->value == '6m') {
            return (new \DateTime())->modify('-6 months')->modify('first day of this month')->setTime(23, 59, 59);
        }
    }

    public function getDateStop()
    {
        return (new \DateTime())->modify('last day of this month')->setTime(23, 59, 59);
    }

    public function getRangeFormat()
    {
        return '%y.%m.%d/%H';
    }

    public function getRangeValue()
    {
        return 3600;
    }
}