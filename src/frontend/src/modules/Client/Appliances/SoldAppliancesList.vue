<template>
  <div>
    <widget
      color="green"
      @widgetAction="
        () => {
          showSellApplianceModal = true
          key++
        }
      "
      :button-text="$tc('phrases.assignAppliance', 0)"
      :button="true"
      :title="$tc('phrases.soldAppliances')"
      :button-color="'red'"
    >
      <md-table>
        <md-table-row>
          <md-table-head>{{ $tc("words.name") }}</md-table-head>
          <md-table-head>{{ $tc("words.cost") }}</md-table-head>
          <md-table-head>Down Payment</md-table-head>
          <md-table-head>{{ $tc("words.rate", 1) }}</md-table-head>
        </md-table-row>
        <md-table-row
          v-for="(item, index) in soldAppliancesList"
          :key="index"
          :class="selectedApplianceId === item.id ? 'selected-row' : ''"
          @click="showDetails(soldAppliancesList[index].id)"
        >
          <md-table-cell md-label="Name" md-sort-by="name">
            {{ item.asset.name }}
          </md-table-cell>
          <md-table-cell md-label="Cost" md-sort-by="total_cost">
            {{ moneyFormat(item.total_cost) }}
          </md-table-cell>
          <md-table-cell md-label="Down Payment" md-sort-by="Down Payment">
            {{ moneyFormat(item.down_payment) }}
          </md-table-cell>
          <md-table-cell md-label="Rates" md-sort-by="rate_count">
            {{ item.rate_count }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
    <sell-appliance-modal
      v-if="person"
      :person="person"
      :showSellApplianceModal="showSellApplianceModal"
      @hideModal="() => (showSellApplianceModal = false)"
      :key="key"
    />
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { currency, notify } from "@/mixins"
import SellApplianceModal from "@/modules/Client/Appliances/SellApplianceModal.vue"
import { PersonService } from "@/services/PersonService"

export default {
  name: "SoldAppliancesList",
  components: { Widget, SellApplianceModal },
  mixins: [currency, notify],
  props: {
    soldAppliancesList: {
      required: true,
    },
    personId: {
      required: true,
    },
  },
  data() {
    return {
      personService: new PersonService(),
      currency: this.$store.getters["settings/getMainSettings"].currency,
      selectedApplianceId: null,
      person: null,
      showSellApplianceModal: false,
      key: 0,
    }
  },
  created() {
    this.selectedApplianceId = parseInt(this.$route.params.id)
  },
  mounted() {
    this.getPersonDetails()
  },
  methods: {
    async getPersonDetails() {
      try {
        this.person = await this.personService.getPerson(this.personId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    showDetails(id) {
      this.selectedRow(id)
      this.$router
        .push({ path: "/sold-appliance-detail/" + id })
        .catch((err) => err)
    },
    selectedRow(id) {
      if (this.selectedApplianceId !== id) {
        this.selectedApplianceId = id
      }
    },
  },
}
</script>

<style scoped>
.selected-row {
  background-color: #ccc;
}
</style>
