<template>
    <section id="widget-grid" v-if="isLoaded">
        <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-55 md-small-size-100">
                <client-personal-data :person="person"/>
                <addresses :person-id="person.id" v-if="person!==null"/>
                <sms-history :person-id="personId" person-name="System"/>
            </div>
            <div class="md-layout-item md-size-45 md-small-size-100">
                <payment-flow/>
                <payment-detail/>
            </div>
            <div class="md-layout-item md-size-100">
                <transactions :personId="personId"/>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
                <div class="client-detail-card">
                    <deferred-payments :person-id="person.id" v-if="person!==null"/>
                </div>
                <div class="client-detail-card">
                    <ticket/>
                </div>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
                <div class="client-detail-card">
                    <client-meter-list :meterList="meters"/>
                </div>
                <div class="client-detail-card">
                    <client-map :meterIds="meters"/>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
import PaymentFlow from '@/modules/Client/PaymentFlow'
import Transactions from '@/modules/Client/Transactions'
import PaymentDetail from '@/modules/Client/PaymentDetail'
import Ticket from '@/modules/Client/Ticket'
import Addresses from '@/modules/Client/Addresses'
import ClientMeterList from '@/modules/Client/ClientMeterList'
import SmsHistory from '@/modules/Client/SmsHistory'
import ClientPersonalData from '@/modules/Client/ClientPersonalData'
import DeferredPayments from '@/modules/Client/DeferredPayments'
import { PersonService } from '@/services/PersonService'
import ClientMap from '@/modules/Client/ClientMap'
import moment from 'moment'

export default {
    name: 'Client',
    data () {
        return {
            personService: new PersonService(),
            personId: null,
            isLoaded: false,
            editPerson: false,
            person: null,
            meters: [],

        }
    },
    components: {
        DeferredPayments,
        ClientPersonalData,
        SmsHistory,
        ClientMeterList,
        PaymentFlow,
        Transactions,
        PaymentDetail,
        Ticket,
        Addresses,
        ClientMap
    },
    created () {
        this.personId = this.$route.params.id
        this.getDetails(this.personId)
    },
    mounted () {

    },
    destroyed () {
        this.$store.state.person = null
        this.$store.state.meters = null
    },

    methods: {
        async getDetails (id) {
            try {

                this.person = await this.personService.getPerson(id)
                this.isLoaded = true
                this.$store.state.person = this.person
                this.meters = []

                for (let i in this.person.meters) {
                    this.meters.push(this.person.meters[i].meter.id)
                }
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        dateForHumans (date) {
            return moment(date, 'YYYY-MM-DD HH:mm:ss').fromNow()
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        }
    }
}
</script>
<style>
.asd__inner-wrapper {
    margin-left: 0 !important;
}

[data-letters]:before {
    content: attr(data-letters);
    display: inline-block;
    font-size: 1em;
    width: 2.5em;
    height: 2.5em;
    line-height: 2.5em;
    text-align: center;
    border-radius: 50%;
    background: plum;
    vertical-align: middle;
    margin-right: 1em;
    color: white;
}


</style>
