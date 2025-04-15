<template>
  <widget
    :title="$tc('phrases.lastTransactions')"
    :paginator="userTransactionsService.paginator"
    color="green"
    :subscriber="subscriber"
  >
    <md-table
      style="width: 100%"
      v-model="userTransactionsService.list"
      md-card
      md-fixed-header
    >
      <md-table-row
        @click="loadTransaction(item.id)"
        slot="md-table-row"
        slot-scope="{ item }"
      >
        <md-table-cell
          :md-label="$tc('phrases.paymentType')"
          md-sort-by="paymentType"
          md-numeric
        >
          {{ item.paymentType }}
        </md-table-cell>
        <md-table-cell :md-label="$tc('words.sender')" md-sort-by="sender">
          {{ item.sender }}
        </md-table-cell>
        <md-table-cell :md-label="$tc('words.amount')" md-sort-by="amount">
          {{ moneyFormat(item.amount) }}
        </md-table-cell>
        <md-table-cell :md-label="$tc('phrases.paidFor')" md-sort-by="type">
          {{ item.type }}
        </md-table-cell>
        <md-table-cell
          :md-label="$tc('phrases.paymentService')"
          md-sort-by="paymentService"
        >
          {{ item.paymentService }}
        </md-table-cell>
        <md-table-cell
          :md-label="$tc('phrases.createdAt')"
          md-sort-by="createdAt"
        >
          {{ timeForHuman(item.createdAt) }}
        </md-table-cell>
      </md-table-row>
    </md-table>
  </widget>
</template>

<script>
import { currency } from "@/mixins/currency"
import { timing } from "@/mixins/timing"
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { UserTransactionsService } from "@/services/UserTransactionsService"

export default {
  name: "Transactions",
  components: { Widget },
  mixins: [currency, timing],
  props: {
    personId: null,
  },
  data() {
    return {
      userTransactionsService: new UserTransactionsService(this.personId),
      subscriber: "client-transactions",
      articleClass: "col-sm-12",
      transactions: [],
      currentPage: 1,
      from: 0,
      to: 0,
      total: 0,
      totalPages: 0,
      currency: this.$store.getters["settings/getMainSettings"].currency,
    }
  },

  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    // this.getTransactions(0)
    //pageSetUp()
    window.addEventListener("resize", this.handleResize)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(sub, data) {
      if (sub !== this.subscriber) return
      this.userTransactionsService.updateList(data)
      EventBus.$emit("dataLoaded")
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.userTransactionsService.list.length,
      )
    },
    handleResize() {
      console.log("resize")
    },
    toggleArticleClass() {
      if (this.articleClass === "col-sm-6") {
        this.articleClass = "col-sm-12"
      } else {
        this.articleClass = "col-sm-6"
      }
    },

    loadTransaction(transactionId) {
      this.$router.push({ path: "/transactions/" + transactionId })
    },
  },
}
</script>
<style scoped lang="scss">
.pagination {
  color: #ac2925;
  list-style: none;
  display: flex;

  li {
    list-style: none;
    display: inline-block;
    padding: 5px;
    margin: 1px;
    background-color: #f7f7f7;
  }

  .active {
    background-color: #dddddd;
  }
}
</style>
