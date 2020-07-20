<?php

namespace Arxy\FilesBundle\Form\Type;

use Arxy\FilesBundle\Form\EventListener\FileUploadListener;
use Arxy\FilesBundle\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileType extends AbstractType
{
    /**
     * @var Manager
     */
    private $fileManager;

    /**
     * FileType constructor.
     *
     * @param Manager $fileManager
     */
    public function __construct(Manager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FileUploadListener($this->fileManager));
    }

    public function getParent()
    {
        return \Symfony\Component\Form\Extension\Core\Type\FileType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', null);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['file'] = $form->getData();
    }

    public function getBlockPrefix()
    {
        return 'arxy_file';
    }
}
