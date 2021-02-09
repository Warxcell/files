<?php

namespace Arxy\FilesBundle\Form\Type;

use Arxy\FilesBundle\Form\EventListener\FileUploadListener;
use Arxy\FilesBundle\ManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileType extends AbstractType
{
    /** @var ManagerInterface */
    private $fileManager;

    public function __construct(ManagerInterface $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fileOptions = $options['input_options'];
        $fileOptions['mapped'] = false;
        $fileOptions['label'] = false;
        $fileOptions['multiple'] = $options['multiple'];
        $fileOptions['required'] = $options['required'];

        $builder->add('file', SymfonyFileType::class, $fileOptions);

        $builder->addEventSubscriber(new FileUploadListener($options['manager'], $options['multiple']));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(
            'data_class',
            function (Options $options) {
                return $options['multiple'] ? null : $options['manager']->getClass();
            }
        );
        $resolver->setDefault('error_bubbling', false);
        $resolver->setDefault('input_options', []);
        $resolver->setDefault('multiple', false);
        $resolver->setDefault('compound', true);
        $resolver->setDefault('manager', $this->fileManager);
        $resolver->setAllowedTypes('manager', ManagerInterface::class);
    }

    public function getBlockPrefix()
    {
        return 'arxy_file';
    }
}
