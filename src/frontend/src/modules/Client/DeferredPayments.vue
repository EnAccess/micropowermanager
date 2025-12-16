<template>
  <div>
    <widget
      :class="'col-sm-6 col-md-5'"
      :button-text="$tc('phrases.assignAppliance', 0)"
      :button="true"
      :title="$tc('phrases.soldAppliances')"
      :button-color="'red'"
      color="green"
      :subscriber="subscriber"
      @widgetAction="
        () => {
          showSellApplianceModal = true
          key++
        }
      "
    >
      <div>
        <md-table>
          <md-table-row>
            <md-table-head>{{ $tc("words.name") }}</md-table-head>
            <md-table-head>{{ $tc("words.cost") }}</md-table-head>
            <md-table-head>Down Payment</md-table-head>
            <md-table-head>
              {{ $tc("words.rate", 1) }}
            </md-table-head>
          </md-table-row>
          <md-table-row
            v-for="(item, index) in appliancePersonService.list"
            :key="index"
            @click="showDetails(index)"
          >
            <md-table-cell md-label="Name" md-sort-by="name">
              {{ item.appliance.name }}
            </md-table-cell>
            <md-table-cell md-label="Cost" md-sort-by="total_cost">
              {{ moneyFormat(item.total_cost) }}
            </md-table-cell>
            <md-table-cell md-label="Down Payment" md-sort-by="down_payment">
              {{ moneyFormat(item.down_payment) }}
            </md-table-cell>
            <md-table-cell md-label="Rates" md-sort-by="rate_count">
              {{ item.rate_count }}
            </md-table-cell>
          </md-table-row>
        </md-table>
      </div>
    </widget>
    <sell-appliance-modal
      :person="person"
      :showSellApplianceModal="showSellApplianceModal"
      @hideModal="() => (showSellApplianceModal = false)"
      :key="key"
    />
  </div>
</template>

<script>
import { AppliancePersonService } from "@/services/AppliancePersonService"
import { currency, notify } from "@/mixins"
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import SellApplianceModal from "@/modules/Client/Appliances/SellApplianceModal.vue"

export default {
  name: "DeferredPayments",
  mixins: [currency, notify],
  components: { SellApplianceModal, Widget },
  props: {
    personId: Number,
    person: {
      type: Object,
      required: true,
    },
  },
  mounted() {
    this.getApplianceList()
  },
  data() {
    return {
      subscriber: "person-appliance",
      appliancePersonService: new AppliancePersonService(),
      adminId:
        this.$store.getters["auth/authenticationService"].authenticateUser.id,
      selectedAppliance: null,
      headers: [
        this.$tc("words.name"),
        this.$tc("words.cost"),
        this.$tc("words.rate", 1),
      ],
      showSellApplianceModal: false,
      key: 0,
    }
  },

  methods: {
    showDetails(index) {
      this.selectedAppliance = this.appliancePersonService.list[index]
      this.$router.push("/sold-appliance-detail/" + this.selectedAppliance.id)
    },
    async getApplianceList() {
      if (!this.$can("appliances")) {
        return
      }
      try {
        await this.appliancePersonService.getPersonAppliances(this.personId)
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.appliancePersonService.list.length,
        )
      } catch (e) {
        if (e.response && e.response.status === 403) {
          console.warn("Assets/Deferred payments: Insufficient permissions")
          return
        }
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
