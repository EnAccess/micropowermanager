<template>
    <div>
        <div class="md-layout">
            <div class="md-layout-item md-subheader">
                {{ $tc('phrases.waveMoneyTransaction') }}
            </div>
            <div class="md-layout-item md-subheader n-font">
                {{ot.manufacturer_transaction_type}}
            </div>
        </div>
        <hr class="hr-d">
        <div class="md-layout">
            <div class="md-layout-item md-subheader">
                {{ $tc('phrases.orderId') }}
            </div>
            <div class="md-layout-item md-subheader n-font">
                {{ot.order_id}}
            </div>
        </div>
        <hr class="hr-d">
        <div class="md-layout">
            <div class="md-layout-item md-subheader">
                {{ $tc('words.transactionId') }}
            </div>
            <div class="md-layout-item md-subheader n-font">
                {{ot.external_transaction_id}}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WaveMoneyTransaction',
    props: ['ot'],
    data () {
        return {


        }
    }
}
</script>

<style scoped>

</style>