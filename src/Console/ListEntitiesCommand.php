<?php declare(strict_types=1);

namespace SeStep\Typeful\Console;

use Nette\Localization\Translator;
use SeStep\Typeful\Service\EntityDescriptorRegistry;
use Symfony\Component\Console;

class ListEntitiesCommand extends Console\Command\Command
{
    /** @var EntityDescriptorRegistry */
    private $descriptorRegistry;
    /** @var Translator */
    private $translator;

    public function __construct(string $name, EntityDescriptorRegistry $descriptorRegistry, Translator $translator = null)
    {
        parent::__construct($name);
        $this->descriptorRegistry = $descriptorRegistry;
        $this->translator = $translator;
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $table = new Console\Helper\Table($output);
        $table->setHeaders(['Code', 'Entity']);
        foreach ($this->descriptorRegistry->getDescriptors() as $name => $descriptor) {
            $table->addRow([$name, $this->translator ? $this->translator->translate($name) : $name]);
        }

        $table->render();
    }
}
