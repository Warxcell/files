<?php

declare(strict_types=1);

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
    private ManagerInterface $fileManager;

    public function __construct(ManagerInterface $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fileOptions = $options['input_options'];
        $fileOptions['mapped'] = false;
        $fileOptions['label'] = false;
        $fileOptions['multiple'] = $options['multiple'];
        $fileOptions['required'] = $options['required'];

        $builder->add('file', SymfonyFileType::class, $fileOptions);

        $builder->addEventSubscriber(new FileUploadListener($options['manager'], $options['multiple']));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'data_class',
            static fn(Options $options): ?string => $options['multiple'] ? null : $options['manager']->getClass()
        );
        $resolver->setDefault('empty_data', null);
        $resolver->setDefault('input_options', []);
        $resolver->setDefault('multiple', false);
        $resolver->setDefault('manager', $this->fileManager);
        $resolver->setAllowedTypes('manager', ManagerInterface::class);
    }

    public function getBlockPrefix(): string
    {
        return 'arxy_file';
    }
}
