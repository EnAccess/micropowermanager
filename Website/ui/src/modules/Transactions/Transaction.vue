<template>
    <section id="widget-grid" v-if="transaction">
        <div class="row">
            <div class="md-layout md-gutter">
                <div
                    class="md-layout-item  md-xlarge-size-50  md-large-size-50 md-medium-size-50  md-small-size-100 md-xsmall-size-100">
                    <div class="transaction-detail-card">
                        <widget :title="$tc('phrases.providerSpecificInformation')" :show-spinner="false">
                            <md-card>
                                <md-card-content>
                                    <vodacom-transaction-detail
                                        :ot="ot"
                                        v-if="transaction.original_transaction_type === 'vodacom_transaction'"
                                    />
                                    <airtel-transaction-detail
                                        :ot="ot"
                                        v-if="transaction.original_transaction_type === 'airtel_transaction'"
                                    />
                                    <agent-transaction-detail
                                        :ot="ot"
                                        v-if="transaction.original_transaction_type === 'agent_transaction'"
                                    />
                                    <third-party-transaction :ot="ot"
                                                             v-if="transaction.original_transaction_type === 'third_party_transaction'"
                                    />
                                    <wave-money-transaction :ot="ot"
                                                            v-if="transaction.original_transaction_type === 'wave_money_transaction'"
                                    />

                                    <swifta-transaction :ot="ot"
                                                        v-if="transaction.original_transaction_type === 'swifta_transaction'"
                                    />
                                    <wave-com-transaction :ot="ot"
                                                          v-if="transaction.original_transaction_type === 'wavecom_transaction'"
                                    />

                                    <third-party-transaction :ot="ot" v-else/>
                                </md-card-content>
                            </md-card>
                        </widget>
                    </div>

                </div>

                <div
                    class="md-layout-item  md-xlarge-size-50  md-large-size-50 md-medium-size-50  md-small-size-100 md-xsmall-size-100">
                    <div class="transaction-detail-card">
                        <widget
                            :title="$tc('words.detail',2)"
                            :show-spinner="false">
                            <md-card>
                                <md-card-content>
                                    <div class="md-layout">
                                        <div class="md-layout-item md-subheader">{{ $tc('words.sender') }}</div>
                                        <div class="md-layout-item md-subheader n-font">{{ transaction.sender }}</div>
                                    </div>
                                    <hr class="hr-d">
                                    <div class="md-layout">
                                        <div class="md-layout-item md-subheader">{{ $tc('words.amount') }}</div>
                                        <div class="md-layout-item md-subheader n-font">
                                            {{ readable(transaction.amount) }}
                                        </div>
                                    </div>
                                    <hr class="hr-d">
                                    <div class="md-layout">
                                        <div class="md-layout-item md-subheader">{{ $tc('phrases.paymentType') }}</div>
                                        <div class="md-layout-item md-subheader n-font"><span
                                            v-text="transaction.type === 'energy' ? $tc('words.energy') : $tc('phrases.deferredPayment')"></span>
                                        </div>
                                    </div>
                                    <hr class="hr-d">
                                    <div class="md-layout">
                                        <div class="md-layout-item md-subheader">{{ $tc('words.meter') }}</div>
                                        <div class="md-layout-item md-subheader n-font"
                                             v-if="transaction.payment_histories[0].paymentHistory">
                                            <router-link
                                                :to="{path: '/meters/' + transaction.message}"
                                                class="nav-link"
                                            >{{ transaction.message }}
                                            </router-link>
                                        </div>
                                        <div class="md-layout-item md-subheader n-font" v-else>
                                            {{ transaction.message }}
                                        </div>
                                    </div>
                                    <hr class="hr-d">
                                    <div class="md-layout">
                                        <div class="md-layout-item md-subheader">{{ $tc('words.customer') }}</div>
                                        <div class="md-layout-item md-subheader n-font"
                                             v-if="transaction.payment_histories[0].paymentHistory">
                                            <router-link
                                                :to="{path: '/people/' + personId}"
                                                class="nav-link"
                                            >{{ personName }}
                                            </router-link>
                                        </div>
                                        <div class="md-layout-item md-subheader n-font" v-else>
                                            {{ transaction.payment_histories[0].personName }}
                                        </div>
                                    </div>
                                    <hr class="hr-d">
                                    <div class="md-layout">
                                        <div class="md-layout-item md-subheader">{{ $tc('words.date') }}</div>
                                        <div class="md-layout-item md-subheader n-font">
                                            {{ timeForHuman(transaction.created_at) }}
                                            <small>{{ transaction.created_at }}</small></div>
                                    </div>
                                </md-card-content>
                            </md-card>
                        </widget>
                    </div>
                </div>
            </div>
            <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-50 md-small-size-100">
                    <div class="transaction-detail-card">
                        <widget
                            title="Transaction Processing"
                            :show-spinner="false">
                            <md-card>
                                <div v-if="transaction.original_transaction_type === 'third_party_transaction'">
                                    <md-card-content>
                                        <div class="md-layout md-gutter md-size-100">
                                            <ul style="margin: auto">
                                                <li>{{ $tc('phrases.untraceableTransaction') }}</li>
                                            </ul>


                                        </div>
                                    </md-card-content>
                                </div>
                                <div v-else>

                                    <md-card-content v-if="ot.status===1">
                                        <div class="md-layout md-gutter md-size-100">
                                            <div class="md-layout-item md-size-55" style="margin: auto;">
                                                <payment-history-chart :paymentdata="transaction.payment_histories"/>
                                            </div>
                                            <div class="md-layout-item md-size-45">
                                                <md-table v-if="transaction.payment_histories[0].paymentHistory">
                                                    <md-table-row>
                                                        <md-table-head>{{ $tc('phrases.paidFor') }}</md-table-head>
                                                        <md-table-head>{{ $tc('words.amount') }}</md-table-head>
                                                    </md-table-row>
                                                    <md-table-row v-for="(p,i) in transaction.payment_histories"
                                                                  :key="i">
                                                        <md-table-cell><p> {{ p.payment_type }}</p>
                                                        </md-table-cell>
                                                        <md-table-cell> {{ readable(p.amount) }}
                                                        </md-table-cell>
                                                    </md-table-row>
                                                </md-table>
                                            </div>

                                        </div>
                                    </md-card-content>
                                    <md-card-content v-if="ot.status===-1">
                                        <h2>Transaction cancelled</h2>
                                        <md-list class="md-double-line">
                                            <md-subheader style="color:#a81e10"> {{
                                                    $tc('phrases.transactionCancelled')
                                                }}
                                            </md-subheader>

                                            <md-list-item :key="conflict.id" v-for="conflict in ot.conflicts">
                                                <span class="margin-top-5">{{ conflict.state }}</span>
                                            </md-list-item>
                                        </md-list>
                                    </md-card-content>
                                </div>

                            </md-card>
                        </widget>
                    </div>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100" v-if="transaction.sms">
                    <div class="transaction-detail-card">
                        <widget :title="$tc('phrases.outgoingSms')"
                                :show-spinner="false"
                                v-show="(transaction.original_transaction_type !== 'agent_transaction' && transaction.original_transaction_type !== 'third_party_transaction')"
                        >
                            <md-card>
                                <md-card-content>

                                    <div class="md-layout md-gutter md-size-100">
                                        <div class="md-layout-item md-subheader md-size-20">{{ $tc('words.to') }}</div>
                                        <div class="md-layout-item md-subheader md-size-80">
                                            {{ transaction.sms.receiver }}
                                        </div>
                                    </div>
                                    <div class="md-layout md-gutter md-size-100">
                                        <div class="md-layout-item md-subheader md-size-20">{{ $tc('words.body') }}
                                        </div>
                                        <div class="md-layout-item md-subheader md-size-75 message-box">

                                            {{ transaction.sms.body }}

                                        </div>
                                    </div>

                                </md-card-content>
                            </md-card>
                        </widget>
                    </div>
                </div>
            </div>


        </div>

    </section>
</template>

<script>
import { timing } from '@/mixins/timing'
import { currency } from '@/mixins/currency'
import VodacomTransactionDetail from '@/modules/Transactions/VodacomTransactionDetail'
import PaymentHistoryChart from '@/modules/Transactions/PaymentHistoryChart'
import AirtelTransactionDetail from '@/modules/Transactions/AirtelTransactionDetail'
import AgentTransactionDetail from '@/modules/Agent/AgentTransactionDetail'
import Widget from '@/shared/widget'
import { TransactionService } from '@/services/TransactionService'
import { PersonService } from '@/services/PersonService'
import ThirdPartyTransaction from '@/modules/Transactions/ThirdPartyTransaction'
import WaveMoneyTransaction from '@/modules/Transactions/WaveMoneyTransaction'
import SwiftaTransaction from '@/modules/Transactions/SwiftaTransaction'
import WaveComTransaction from '@/modules/Transactions/WaveComTransaction.vue'
import { notify } from '@/mixins/notify'
export default {
    name: 'Transaction',
    mixins: [timing, currency, notify],
    components: {
        WaveComTransaction,
        SwiftaTransaction,
        WaveMoneyTransaction,
        ThirdPartyTransaction,
        AirtelTransactionDetail,
        Widget,
        VodacomTransactionDetail,
        AgentTransactionDetail,
        PaymentHistoryChart
    },
    created () {
        this.transactionId = this.$route.params.id
    },
    mounted () {
        this.getDetail(this.transactionId)
    },
    data () {
        return {
            transactionService: new TransactionService(),
            personService: new PersonService(),
            transactionId: null,
            transaction: null,
            personName: null,
            personId: null,
            showCustomer: true
        }
    },
    computed: {
        ot: function () {
            return this.transaction.original_transaction
        }
    },
    methods: {
        async getDetail (id) {

            try {
                this.transaction = await this.transactionService.getTransaction(id)
                if (this.transaction.payment_histories[0].paymentHistory === true) {
                    await this.getRelatedPerson(this.transaction.payment_histories[0].payer_id)
                }
            } catch (e) {
                this.alertNotify('error', e.message)
            }

        },
        async getRelatedPerson (personId) {
            try {
                let person = await this.personService.getPerson(personId)
                this.personName =
                    person.name + ' ' + person.surname
                this.personId = person.id
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        }
    }
}
</script>

<style scoped>
.transaction-detail-card {
    margin-top: 1rem !important;
    margin-right: 1rem !important;
}

.n-font {
    font-weight: 100 !important;
}

.hr-d {
    height: 1pt;
    margin: auto;
    padding: 0;
    display: block;
    border: 0;
    /* transition: margin-left .3s cubic-bezier(.4,0,.2,1); */
    /* will-change: margin-left; */
    background-color: rgba(0, 0, 0, 0.12);
}

.message-box {
    padding: 10px;
    background-color: #f5e8e8;
    -moz-border-radius: 10px;
    border-radius: 14px;
    margin-top: 2vh;


}

p:first-letter {
    text-transform: capitalize;
}
</style>