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
            v-for="(item, index) in assetPersonService.list"
            :key="index"
            @click="showDetails(index)"
          >
            <md-table-cell md-label="Name" md-sort-by="name">
              {{ item.asset.name }}
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
import { AssetRateService } from "@/services/AssetRateService"
import { AssetPersonService } from "@/services/AssetPersonService"
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
    this.getAssetList()
  },
  data() {
    return {
      subscriber: "person-asset",
      assetRateService: new AssetRateService(),
      assetPersonService: new AssetPersonService(),
      adminId:
        this.$store.getters["auth/authenticationService"].authenticateUser.id,
      selectedAsset: null,
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
      this.selectedAsset = this.assetPersonService.list[index]
      this.$router.push("/sold-appliance-detail/" + this.selectedAsset.id)
    },
    async getAssetList() {
      try {
        await this.assetPersonService.getPersonAssets(this.personId)
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.assetPersonService.list.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
