<?php declare(strict_types=1);

namespace SeStep\Typeful\Console;

use Nette\Localization\Translator;
use SeStep\Typeful\Service\TypeRegistry;
use Symfony\Component\Console;

class ListTypesCommand extends Console\Command\Command
{
    /** @var TypeRegistry */
    private $typeRegistry;
    /** @var Translator */
    private $translator;

    public function __construct(string $name, TypeRegistry $descriptorRegistry, Translator $translator = null)
    {
        parent::__construct($name);
        $this->typeRegistry = $descriptorRegistry;
        $this->translator = $translator;
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $table = new Console\Helper\Table($output);
        $table->setHeaders(['Code', 'Type']);
        foreach ($this->typeRegistry->getTypesLocalized() as $type) {
            $table->addRow([$type, $this->translator ? $this->translator->translate($type) : $type]);
        }

        $table->render();
    }
}
