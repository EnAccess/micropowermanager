<?php

namespace App\Console\Commands;

use App\Services\ClusterService;

use App\Models\Cluster;
use App\Services\ClusterManagerService;
use App\Services\MailService;
use App\Services\PaymentHistoryService;
use App\Services\PdfService;
use App\Services\PersonService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendNonPayingCustomerMailCommand extends Command
{
    private const EMAIL_TEMPLATE = 'templates.mail.non_paying_mail';
    private const REPORT_TEMPLATE = 'templates.mail.non_paying_pdf';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-non-paying-customer-mail {--start-date=} {--end-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a report that includes a list of customers
     that didnt buy anything in the given period';

    private PaymentHistoryService $paymentHistoryService;
    private ClusterService $clusterService;
    private ClusterManagerService $clusterManagerService;
    private PdfService $pdfService;
    private PersonService $personService;


    public function __construct(
        PaymentHistoryService $paymentHistoryService,
        ClusterService $clusterService,
        ClusterManagerService $clusterManagerService,
        PdfService $pdfService,
        PersonService $personService,
    ) {
        parent::__construct();
        $this->paymentHistoryService = $paymentHistoryService;
        $this->clusterService = $clusterService;
        $this->clusterManagerService = $clusterManagerService;
        $this->pdfService = $pdfService;
        $this->personService = $personService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MailService $mailService): int
    {
        $startDate = $this->option('start-date') ? CarbonImmutable::make(
            $this->option('start-date')
        ) : CarbonImmutable::make('first day of last month');
        $endDate = $this->option('end-date') ? CarbonImmutable::make($this->option('end-date')) : CarbonImmutable::make(
            'last day of last month'
        );
        $this->info($startDate->format('Y-m-d') . '- ' . $endDate->format('Y-m-d'));

        $clusters = $this->clusterService->getClusterList();

        $generatedPdfs = [];

        //fetch non-paying customers in all clusters for the given time range
        $clusters->each(function (Cluster $cluster) use ($startDate, $endDate, &$generatedPdfs, $mailService) {
            $nonPayingCustomers = [];
            $this->personService->livingInCluster($cluster->id)->chunk(
                50,
                function (Collection $people) use ($startDate, $endDate, &$nonPayingCustomers, $cluster) {
                    $customersToExclude = $this->paymentHistoryService->findPayingCustomersInRange(
                        $people->pluck('id')->toArray(),
                        $startDate,
                        $endDate
                    );
                    $nonPayingCustomersInRange = array_diff(
                        $people->pluck('id')->toArray(),
                        $customersToExclude->pluck('customer_id')->toArray()
                    );

                    if (count($nonPayingCustomersInRange)) {
                        $nonPayingCustomers = array_merge($nonPayingCustomers, $nonPayingCustomersInRange);
                    }
                }
            );

            $generatedPdfs[$cluster->name] = $this->pdfService->generatePdfFromView(
                self::REPORT_TEMPLATE,
                [
                    'title' => $cluster->name,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'customers' => $this->personService->getBulkDetails($nonPayingCustomers)->get()
                ]
            );


            //send mail
            $mailService->sendWithAttachment(
                self::EMAIL_TEMPLATE,
                [
                    'manager' => $cluster->manager,
                    'cluster_name' => $cluster->name,
                    'period' => $startDate->format('d-m') . ' & ' . $endDate->format('d-m-Y')
                ],
                [
                    'to' => $cluster->manager->email,
                    'from' => 'alchalade@gmail.com',
                    'title' => 'Monthly payment report for ' . $cluster->name,
                ],
                [$generatedPdfs[$cluster->name]]
            );
        });


        return 0;
    }
}
