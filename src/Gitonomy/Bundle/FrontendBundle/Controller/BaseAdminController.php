<?php

namespace Gitonomy\Bundle\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Controller for repositories actions.
 *
 * @author Julien DIDIER <julien@jdidier.net>
 */

abstract class BaseAdminController extends BaseController
{
    public function listAction()
    {
        $className  = $this->getRepository()->getClassName();
        $repository = $this->getRepository();
        $objects    = $repository->findAll();

        return $this->render('list', array(
           'objects' => $objects,
        ));
    }

    public function createAction()
    {
        $className = $this->getRepository()->getClassName();
        $object    = new $className();
        $form      = $this->createForm($this->getFormType($className), $object);
        $request   = $this->getRequest();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($object);
                $em->flush();

                $this->get('session')->setFlash('success',
                    sprintf('%s "%s" saved.',
                        $this->getIdentifier($className),
                        $object->__toString()
                    )
                );

                return $this->redirect($this->generateUrl($this->getRouteName('list')));
            }
        }

        return $this->render('create', array(
           'form' => $form->createView(),
        ));
    }

    public function editAction($id)
    {
        $className = $this->getRepository()->getClassName();

        if (!$object = $this->getRepository()->find($id)) {
            throw new HttpException(404, sprintf('No role found with id "%d".', $id));
        }

        $form    = $this->createForm($this->getFormType($className), $object);
        $request = $this->getRequest();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();
                $em->flush();

                $this->get('session')->setFlash('success',
                    sprintf('%s "%s" updated.',
                        $this->getIdentifier($className),
                        $object->__toString()
                    )
                );

                return $this->redirect($this->generateUrl($this->getRouteName('list')));
            }
        }

        return $this->render('edit', array(
            'object' => $object,
            'form'   => $form->createView(),
        ));
    }

    public function deleteAction($id)
    {
        $className = $this->getRepository()->getClassName();

        if (!$object = $this->getRepository()->find($id)) {
            throw new HttpException(404, sprintf('No %s found with id "%d".', $className, $id));
        }

        $form    = $this->createFormBuilder()->getForm();
        $request = $this->getRequest();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();
                $em->remove($object);
                $em->flush();

                $this->get('session')->setFlash('success',
                    sprintf('%s "%s" deleted.',
                        $this->getIdentifier($className),
                        $object->__toString()
                    )
                );


                return $this->redirect($this->generateUrl($this->getRouteName('list')));
            }
        }

        return $this->render('delete', array(
            'object' => $object,
            'form'   => $form->createView(),
        ));
    }

    protected function getFormType($className)
    {
        $className = $this->getIdentifier($className);
        $formName  = 'Gitonomy\Bundle\FrontendBundle\Form\Admin\\'.$className.'Type';

        return new $formName();
    }

    protected function getRouteName($route)
    {
        $className = get_class($this);
        $route     = $this->getIdentifier($className);
        $route     = strtolower(preg_replace('/Controller$/', '', $route));

        return 'gitonomyfrontend_'.$route.'_list';
    }

    protected function getViewName($className, $view)
    {
        $className = $this->getIdentifier($className);

        return 'GitonomyFrontendBundle:Admin'.$className.':'.$view.'.html.twig';
    }

    protected function getIdentifier($className)
    {
        return substr($className, strrpos($className, '\\') + 1);
    }

    public function render($view, array $parameters = array(), Response $response = null)
    {
        $className     = $this->getRepository()->getClassName();
        $objectType    = strtolower($this->getIdentifier($className));
        $templating    = $this->container->get('templating');
        $viewDirectory = 'Admin'.$className;

        $parameters = array_merge(
            array(
                'object_type'       => strtolower($this->getIdentifier($className)),
                'route_prefix'      => 'gitonomyfrontend_admin'.$objectType,
                'controller_prefix' => 'GitonomyFrontendBundle:Admin'.$this->getIdentifier($className),
            ),
            $parameters
        );

        $template = new TemplateReference('GitonomyFrontendBundle', $viewDirectory, $view, 'html', 'twig');
        if ($templating->exists($template)) {
            $view = $template->getLogicalName();
        } else {
            $view = 'GitonomyFrontendBundle:BaseAdmin:'.$view.'.html.twig';
        }

        return parent::render($view, $parameters, $response);
    }

    abstract protected function getRepository();
}
