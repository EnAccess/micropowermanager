<template>
    <div>

        <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-100" style="margin:auto">
                <header>Online payment for {{ $store.getters['settings/getMainSettings'].companyName }}}</header>
            </div>

            <div
                class="md-layout-item md-small-size-100  md-xsmall-size-100 md-medium-size-100  md-size-50">
                <form @submit.prevent="submitPaymentRequestForm" data-vv-scope="Payment-Form" class="Payment-Form">
                    <div class="md-layout md-gutter">
                        <div
                            class="md-layout-item  md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50">
                            <md-field
                                :class="{'md-invalid': errors.has('Payment-Form.meterSerial')}">
                                <label for="meterSerial">Meter Serial Number</label>
                                <md-input
                                    id="meterSerial"
                                    name="meterSerial"
                                    v-model="paymentService.paymentRequest.meterSerial"
                                    v-validate="'required|min:3'"
                                />
                                <span
                                    class="md-error">{{ errors.first('Payment-Form.meterSerial') }}</span>
                            </md-field>
                        </div>

                        <div
                            class="md-layout-item  md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50">
                            <md-field
                                :class="{'md-invalid': errors.has('Payment-Form.amount')}">
                                <label for="amount">Amount</label>
                                <md-input
                                    id="amount"
                                    name="amount"
                                    v-model="paymentService.paymentRequest.amount"
                                    v-validate="'required|decimal:2'"
                                />
                                <span
                                    class="md-error">{{ errors.first('Payment-Form.amount') }}</span>
                            </md-field>
                        </div>
                        <div class="md-layout-item  md-size-100">
                            <md-button class="md-raised md-primary" type="submit">Make Payment</md-button>
                        </div>
                    </div>
                </form>
            </div>
            <md-progress-bar md-mode="indeterminate" v-if="loading"/>
        </div>
    </div>
</template>

<script>
import { PaymentService } from '../../services/PaymentService'

export default {
    name: 'Payment',
    data () {
        return {
            paymentService: new PaymentService(),
            loading: false,
        }
    },
    computed: {
        companyName () {
            return this.$route.params.companyName
        },
    },
    methods: {
        async submitPaymentRequestForm () {
            const companyId = this.$route.params.id
            let validator = await this.$validator.validateAll('Payment-Form')
            if (!validator) {
                return
            }
            try {
                this.loading = true
                const data = await this.paymentService.startTransaction(companyId)
                this.$swal({
                    title: 'Success! you will be redirected to the payment page',
                    timer: 2000,
                    timerProgressBar: true,
                }).then((result) => {
                    if (result.dismiss === this.$swal.DismissReason.timer) {
                        window.replace(data.url)
                    }
                })

            } catch (e) {
                this.alertNotify('error', e.message)
            }
            this.loading = false
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    }

}
</script>

<style scoped>

</style>