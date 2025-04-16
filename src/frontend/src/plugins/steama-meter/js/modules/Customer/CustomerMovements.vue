<template>
  <div>
    <widget
      id="transaction-list"
      :title="title"
      :paginator="true"
      :paging_url="transactionsService.pagingUrl"
      :route_name="transactionsService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      :newRecordButton="false"
    >
      <md-table
        v-model="transactionsService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="ID" md-sort-by="id">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="Transaction ID" md-sort-by="transactionId">
            {{ item.transactionId }}
          </md-table-cell>
          <md-table-cell md-label="Amount" md-sort-by="amount">
            {{ moneyFormat(item.amount) }}
          </md-table-cell>
          <md-table-cell md-label="Category" md-sort-by="category">
            {{ item.category }}
          </md-table-cell>
          <md-table-cell md-label="Provider" md-sort-by="provider">
            {{ item.provider }}
          </md-table-cell>
          <md-table-cell md-label="Date" md-sort-by="timestamp">
            {{ item.timestamp }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import { SteamaTransactionsService } from "../../services/SteamaTransactionsService"
import { CustomerService } from "../../services/CustomerService"

export default {
  components: { Widget },
  name: "CustomerMovements",
  data() {
    return {
      transactionsService: new SteamaTransactionsService(),
      customerService: new CustomerService(),
      selectedCustomerId: null,
      subscriber: "customer-movements",
      title: "",
    }
  },
  created() {
    this.selectedCustomerId = this.$route.params.customer_id
    this.transactionsService.pagingUrl =
      "/api/steama-meters/steama-transaction/" + this.selectedCustomerId
    this.transactionsService.routeName =
      "/steama-meters/steama-transaction/" + this.selectedCustomerId
  },
  mounted() {
    this.getCustomerName()
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    async getCustomerName() {
      this.title = await this.customerService.getCustomerName(
        this.selectedCustomerId,
      )
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.transactionsService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactionsService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
