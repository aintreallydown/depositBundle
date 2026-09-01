<?php

namespace aintreallydown\DepositBundle\Controller;

use aintreallydown\DepositBundle\Form\PropertyFormType;
use App\Entity\Property;
use App\Security\Voter\PropertyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

class PropertyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    #[Route('/property/{uid}/deposit/', name: 'property.deposit', priority: 1)]
    public function deposit(
        #[MapEntity(mapping: ['uid' => 'uid'])]
        Property $property, 
        Request $request): Response
    {
        if ($property === null) {
            throw new NotFoundHttpException('Property not found.');
        }

        $this->denyAccessUnlessGranted(PropertyVoter::EDIT, $property);

        $step = $property->getState();

        $slug = 'deposit';
        $nextStep = 'availability';
        $states = Property::STATES;

        if ($step && array_search($step, $states) < array_search($slug, $states)) {
            return $this->redirectToRoute("property.$step", ['uid' => $property->getUid()]);
        }

        $form = $this->createForm(PropertyFormType::class);
        $form->handleRequest($request);

        $extrafields = $property->getExtrafields() ?? [];

        if ($form->isSubmitted() && $form->isValid()) {

            $extrafields['deposit'] = $form->get('deposit')->getData();
            $property->setExtrafields($extrafields);

            $isSave = $form->get('save')->isClicked();

            if ($step === $slug && !$isSave) {
                $property->setState($nextStep);
            }

            $this->em->flush();

            return $isSave
                ? $this->redirectToRoute('properties')
                : $this->redirectToRoute("property.$nextStep", ['uid' => $property->getUid()]);
        }

        $default = $property->getRent() - $property->getCharges();

        $max = $property->isFurnished()
            ? $default * 2
            : $default;

        $deposit = $default === null
            ? $max
            : $extrafields['deposit'] ?? null;

        $form->get('deposit')->setData($deposit);

        return $this->render('@DepositBundle/deposit.html.twig', [
            'form' => $form->createView(),
            'backlink' => $this->generateUrl('property.services', ['uid' => $property->getUid()]),
            'uid' => $property->getUid(),
            'max' => $max,
            'step' => $step,
            'current' => 4,
            'progress' => 11 / count($states),
        ]);
    }
}