<?php

namespace Arxy\FilesBundle\Form\Type;

use Arxy\FilesBundle\Form\EventListener\FileUploadListener;
use Arxy\FilesBundle\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
        $builder->add(
            'file',
            \Symfony\Component\Form\Extension\Core\Type\FileType::class,
            [
                'mapped' => false,
                'label'  => false,
            ]
        );

        $builder->addEventSubscriber(new FileUploadListener($this->fileManager));
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
