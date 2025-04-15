<template>
  <div>
    <add />
    <widget
      id="tariff-list"
      :title="$tc('words.tariff', 2)"
      :button="true"
      :subscriber="subscriber"
      :buttonText="$tc('phrases.newTariff')"
      @widgetAction="showNewTariff"
      color="green"
      :paginator="tariffService.paginator"
    >
      <md-table
        v-model="tariffService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.name')" md-sort-by="name">
            {{ item.name }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.price') + '/ kWh'" md-numeric>
            {{ moneyFormat(item.price) }}
          </md-table-cell>
          <md-table-cell
            :md-label="$tc('phrases.minimumPurchaseAmount')"
            md-numeric
          >
            {{ moneyFormat(item.minimumPurchaseAmount) }}
          </md-table-cell>
          <md-table-cell
            :md-label="$tc('phrases.accessRate')"
            md-numeric
            md-sort-by="accessRate.amount"
          >
            <div v-if="item.accessRate">
              {{ moneyFormat(item.accessRate.amount) }}
            </div>
            <div v-else>-</div>
          </md-table-cell>
          <md-table-cell
            :md-label="$tc('phrases.accessRatePeriodInDays')"
            md-numeric
          >
            <div v-if="item.accessRate.period">
              {{ item.accessRate.period }} {{ $tc("words.day") }}
            </div>
            <div v-else>-</div>
          </md-table-cell>
          <md-table-cell md-label="#">
            <md-button class="md-icon-button" @click="editTariff(item.id)">
              <md-tooltip md-direction="top">
                {{ $tc("words.edit") }}
              </md-tooltip>
              <md-icon>edit</md-icon>
            </md-button>
            <md-button
              class="md-icon-button"
              @click="showConfirmation(item.id)"
            >
              <md-tooltip md-direction="top">
                {{ $tc("words.delete") }}
              </md-tooltip>
              <md-icon>delete</md-icon>
            </md-button>
          </md-table-cell>
        </md-table-row>
      </md-table>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { currency } from "@/mixins/currency"
import Add from "@/modules/Tariff/Add"
import { EventBus } from "@/shared/eventbus"
import { TariffService } from "@/services/TariffService"
import { notify } from "@/mixins/notify"

export default {
  name: "Tariffs",
  mixins: [currency, notify],
  components: { Widget, Add },
  data() {
    return {
      subscriber: "tariff-list",
      tariffService: new TariffService(),
      tariffList: [],
      loading: false,
    }
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("tariffAdded", () => {
      this.getTariffs()
    })
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.tariffService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.tariffService.list.length,
      )
    },
    async getTariffs() {
      try {
        await this.tariffService.getTariffs()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.tariffService.list.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    showNewTariff() {
      EventBus.$emit("showNewTariff")
    },
    addToList(tariff) {
      this.tariffService.addToList(tariff)
    },
    editTariff(id) {
      this.$router.push({ path: "/tariffs/" + id })
    },
    async deleteTariff(id) {
      try {
        this.loading = true
        await this.tariffService.deleteTariff(id)
        this.loading = false
        this.alertNotify("success", this.$tc("phrases.tariffNotify", 1))
        await this.getTariffs()
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async changeUsingMeterTariff(currentId, changeId) {
      try {
        this.loading = true
        await this.tariffService.changeMetersTariff(currentId, changeId)
        this.loading = false
        this.alertNotify("success", this.$tc("phrases.tariffNotify", 2))
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async showConfirmation(id) {
      let countObject = await this.tariffService.tariffUsageCount(id)
      let usageCount = countObject.count
      let tariffs = this.tariffService.list
      let tariffObj = tariffs.reduce((acc, value) => {
        if (value.id !== id) {
          acc[value.id] = value.name
        }
        return acc
      }, {})
      let swalOptions = {
        type: "question",
        title: this.$tc("words.delete"),
        showCancelButton: true,
        confirmButtonText: this.$tc("words.yes"),
        cancelButtonText: this.$tc("words.no"),
      }
      if (usageCount > 0) {
        swalOptions.input = "select"
        swalOptions.inputOptions = tariffObj
        swalOptions.text = this.$tc("phrases.tariffNotify2", 2, {
          usageCount: usageCount,
        })
      } else {
        swalOptions.text = this.$tc("phrases.tariffNotify2", 1)
      }
      this.$swal(swalOptions).then((result) => {
        if (result.value) {
          // eslint-disable-next-line no-constant-condition
          if (typeof result.value == "string") {
            this.changeUsingMeterTariff(id, Number(result.value))
          }
          this.deleteTariff(id)
        }
      })
    },
  },
}
</script>

<style scoped></style>
