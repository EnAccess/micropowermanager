<template>
    <widget
        v-if="transactions!==null"
        :title="$tc('phrases.meterTransaction')"
        class="col-sm-7"
        :id="'meter-transactions'"
        :paginator="transactions.paginator"
        :subscriber="subscriber"
        color="green"
    >
        <md-card>
            <md-card-content>

                <md-table>
                    <md-table-row>
                        <md-table-head v-for="(item, index) in headers" :key="index">{{ item }}
                        </md-table-head>
                    </md-table-row>
                    <md-table-row v-for="token in transactions.tokens" :key="token.id">
                        <md-table-cell v-text="token.transaction.id"></md-table-cell>
                        <md-table-cell

                            v-text="token.transaction.original_transaction_type"
                        ></md-table-cell>
                        <md-table-cell v-text="token.transaction.amount"></md-table-cell>
                        <md-table-cell
                            v-if="token.paid_for_type === 'token'"
                        >Token {{ token.paid_for.token }}
                        </md-table-cell>
                        <md-table-cell v-else>Access Rate</md-table-cell>
                        <md-table-cell
                            v-if="token.paid_for_type === 'token'"
                            v-text="token.paid_for.energy + 'kWh'"
                        ></md-table-cell>
                        <md-table-cell v-else>-</md-table-cell>
                        <md-table-cell v-text="token.created_at"></md-table-cell>
                    </md-table-row>
                </md-table>

            </md-card-content>
        </md-card>
    </widget>
</template>

<script>
import Widget from '../../shared/widget'
import { EventBus } from '../../shared/eventbus'
export default {
    name: 'Transactions.vue',
    components:{ Widget },
    props:{
        transactions:{
            type:Object
        }
    },
    computed: {
        transactionType: () => {
            return this.token.transaction.original_transaction_type
        }
    },
    created () {
        EventBus.$on('pageLoaded', this.reloadList)
    },
    data(){
        return{
            subscriber: 'meter.transactions',
            headers: [this.$tc('words.id'), this.$tc('words.provider'), this.$tc('words.amount'),
                this.$tc('phrases.paidFor'), this.$tc('phrases.inReturn'), this.$tc('words.date')],
            tableName: 'Meter Transactions'
        }
    },
    methods:{
        reloadList (subscriber, data) {
            if (subscriber !== this.subscriber) {
                return
            }
            this.transactions.updateList(data)
            EventBus.$emit('widgetContentLoaded', this.subscriber, this.transactions.tokens.length)
        },
    }
}
</script>

<style scoped>
</style>
