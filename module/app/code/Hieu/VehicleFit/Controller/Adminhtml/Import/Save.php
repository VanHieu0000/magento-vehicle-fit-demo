<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Controller\Adminhtml\Import;

use Hieu\VehicleFit\Model\CsvImporter;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hieu_VehicleFit::import';
    private const MAX_ERRORS_SHOWN = 20;

    public function __construct(
        Context $context,
        private readonly CsvImporter $importer
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/index');
        $file = $this->getRequest()->getFiles('fitment_csv');

        if (!is_array($file) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->messageManager->addErrorMessage(__('Please choose a CSV file.'));
            return $redirect;
        }
        if (strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            $this->messageManager->addErrorMessage(__('Only .csv files are allowed.'));
            return $redirect;
        }

        try {
            $result = $this->importer->import($file['tmp_name']);
            $this->messageManager->addSuccessMessage(__(
                'Imported %1 rows: %2 vehicles, %3 links. %4 rows skipped. Fitment cache cleaned.',
                $result['rows'],
                $result['vehicles'],
                $result['links'],
                count($result['errors'])
            ));
            foreach (array_slice($result['errors'], 0, self::MAX_ERRORS_SHOWN, true) as $line => $error) {
                $this->messageManager->addWarningMessage(__('Line %1: %2', $line, $error));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Import failed, nothing was saved.'));
        }

        return $redirect;
    }
}
