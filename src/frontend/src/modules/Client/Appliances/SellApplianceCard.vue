<template>
  <div>
    <widget color="red" title="Sell Appliance ">
      <md-card class="md-layout-item md-size-100">
        <md-card-content>
          <form novalidate class="md-layout" @submit.prevent="saveAppliance">
            <md-tabs>
              <md-tab id="tab-rate-based" md-label="Main">
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.appliance')),
                  }"
                >
                  <label for="appliance">
                    {{ $tc("words.appliance") }}
                  </label>
                  <md-select
                    :name="$tc('words.appliance')"
                    id="appliance"
                    v-model="applianceTypeIndex"
                  >
                    <md-option disabled value>
                      --{{ $tc("words.select") }}--
                    </md-option>
                    <md-option
                      :value="appliance.id"
                      v-for="appliance in appliances"
                      :key="appliance.id"
                    >
                      {{ appliance.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first($tc($tc("words.appliance"))) }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.cost')),
                  }"
                >
                  <label for="Cost">
                    {{ $tc("words.cost") }}
                  </label>
                  <md-input
                    type="number"
                    :name="$tc('words.cost')"
                    id="Cost"
                    v-model="newAppliance.cost"
                    @keyup="checkDownPayment"
                    v-validate="'required|decimal'"
                  />
                  <span class="md-error">
                    {{ errors.first($tc("phrases.ratesCount")) }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Down Payment'),
                  }"
                >
                  <label for="Down Payment">Down Payment</label>
                  <md-input
                    type="number"
                    name="Down Payment"
                    id="Down Payment"
                    v-model="newAppliance.downPayment"
                    v-validate="'required|decimal'"
                    @keyup="checkDownPayment"
                  />
                  <span class="md-error">
                    {{ errors.first("Down Payment") }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.rate')),
                  }"
                  v-if="applianceRate"
                >
                  <label for="rate">
                    {{ $tc("phrases.ratesCount") }}
                  </label>
                  <md-input
                    type="number"
                    :name="$tc('phrases.ratesCount')"
                    id="rate"
                    v-model="newAppliance.rate"
                    v-validate="'required|integer'"
                  />
                  <span class="md-error">
                    {{ errors.first($tc("words.rate")) }}
                  </span>
                </md-field>
              </md-tab>
              <md-tab id="tab-cost-based" md-label="Plugins">
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.appliance')),
                  }"
                >
                  <label for="appliance">
                    {{ $tc("words.appliance") }}
                  </label>
                  <md-select
                    :name="$tc('words.appliance')"
                    id="appliance"
                    v-model="applianceTypeIndex"
                  >
                    <md-option disabled value>
                      --{{ $tc("words.select") }}--
                    </md-option>
                    <md-option
                      :value="appliance.id"
                      v-for="appliance in appliances"
                      :key="appliance.id"
                    >
                      {{ appliance.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first($tc($tc("words.appliance"))) }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.cost')),
                  }"
                >
                  <label for="Cost">
                    {{ $tc("words.cost") }}
                  </label>
                  <md-input
                    type="number"
                    :name="$tc('words.cost')"
                    id="Cost"
                    v-model="newAppliance.cost"
                    @keyup="checkDownPayment"
                    v-validate="'required|decimal'"
                  />
                  <span class="md-error">
                    {{ errors.first($tc("phrases.ratesCount")) }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has('Down Payment'),
                  }"
                >
                  <label for="Down Payment">Down Payment</label>
                  <md-input
                    type="number"
                    name="Down Payment"
                    id="Down Payment"
                    v-model="newAppliance.downPayment"
                    v-validate="'required|decimal'"
                    @keyup="checkDownPayment"
                  />
                  <span class="md-error">
                    {{ errors.first("Down Payment") }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.rateCost')),
                  }"
                >
                  <label for="rateCost">
                    {{ $tc("words.rateCost") }}
                  </label>
                  <md-input
                    type="number"
                    name="rateCost"
                    id="rateCost"
                    @keyup="calculateRateCountsOnRateCostChange"
                    v-model="rateCost"
                  />
                  <span class="md-error">
                    {{ errors.first($tc("words.rateCost")) }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('words.minimumPayableAmount')),
                  }"
                >
                  <label for="minimumPayableAmount">
                    {{ $tc("words.minimumPayableAmount") }}
                  </label>
                  <md-input
                    type="number"
                    :name="$tc('phrases.minimumPayableAmount')"
                    id="minimumPayableAmount"
                    v-model="minimumPayableAmount"
                    readonly
                    disabled
                  />
                  <span class="md-error">
                    {{ errors.first($tc("words.rate")) }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has($tc('phrases.selectDevice')),
                  }"
                >
                  <label for="device">{{ $tc("phrases.selectDevice") }}:</label>
                  <md-select
                    :name="$tc('phrases.selectDevice')"
                    id="device"
                    v-validate="'required'"
                    v-model="selectedDeviceSerialNumber"
                  >
                    <md-option
                      v-for="meter in person.meters"
                      :value="meter.meter.serial_number"
                      :key="meter.meter.id"
                    >
                      {{ meter.meter.serial_number }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first($tc("phrases.selectDevice")) }}
                  </span>
                </md-field>
              </md-tab>
            </md-tabs>
          </form>
        </md-card-content>
        <md-card-actions>
          <md-button
            v-if="showRatesButton"
            class="md-accent md-raised"
            @click="showRates = true"
          >
            Show Rates Detail
          </md-button>
          <md-button type="submit" class="md-primary md-raised">
            {{ $tc("words.sell") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
    <md-dialog :md-active.sync="showRates">
      <md-dialog-title>
        Cost: {{ moneyFormat(newAppliance.cost) }}
        <br />
        Down Payment :
        {{ moneyFormat(newAppliance.downPayment) }}
        <br />
        Rates: {{ newAppliance.rate }}
      </md-dialog-title>
      <md-dialog-content>
        <div v-if="newAppliance.rate">
          <div v-for="x in parseInt(newAppliance.rate)" :key="x">
            <span v-if="x < 10" style="opacity: 0">0</span>
            {{ x }}&nbsp;-&nbsp;{{
              readable(
                getRate(
                  x,
                  newAppliance.rate,
                  newAppliance.cost - newAppliance.downPayment,
                ),
              )
            }}
            {{ $store.getters["settings/getMainSettings"].currency }}
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button class="md-primary" @click="showRates = false">
          Close
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { ApplianceService } from "@/services/ApplianceService"
import { AssetPersonService } from "@/services/AssetPersonService"
import { currency, notify } from "@/mixins"
import { PersonService } from "@/services/PersonService"
import { TariffService } from "@/services/TariffService"

// These are fixed values in the database
const APPLIANCE_TYPE_SHS_ID = 1
const APPLIANCE_TYPE_SHS_NAME = "Solar Home System"

export default {
  name: "SellApplianceCard",
  components: { Widget },
  mixins: [currency, notify],
  props: {
    personId: {
      required: true,
    },
  },
  data() {
    return {
      newAppliance: {
        id: null,
        cost: null,
        downPayment: null,
        rate: null,
        isShs: false,
      },
      applianceTypeIndex: null,
      adminId: this.$store.getters["auth/getAuthenticateUser"].id,
      applianceRate: true,
      showRates: false,
      personService: new PersonService(),
      applianceService: new ApplianceService(),
      assetPersonService: new AssetPersonService(),
      tariffService: new TariffService(),
      currency: this.$store.getters["settings/getMainSettings"].currency,
      minimumPayableAmount: 0,
      bindToTariff: true,
      person: null,
      selectedDeviceSerialNumber: null,
      showSelectDevice: false,
      selectedApplianceName: "",
      rateCost: 0,
      appliances: [],
    }
  },
  watch: {
    applianceTypeIndex() {
      this.minimumPayableAmount = 0
      const appliance = this.applianceService.list.find(
        (x) => x.id === this.applianceTypeIndex,
      )
      this.newAppliance.id = appliance.id
      this.newAppliance.cost = this.newAppliance.preferredPrice = String(
        appliance.price,
      )
      this.newAppliance.downPayment = 0
      this.newAppliance.rate = 0
      this.selectedApplianceName = String(appliance.name).trim().toLowerCase()
      this.checkIsForSHS(appliance)
    },
  },
  computed: {
    showRatesButton() {
      return this.newAppliance.rate > 1
    },
  },
  created() {
    this.getPerson()
    this.getApplianceList()
  },
  methods: {
    async getApplianceList() {
      try {
        await this.applianceService.getAppliances()
        this.appliances = this.applianceService.list
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveAppliance() {
      let validator = await this.$validator.validateAll()
      if (validator) {
        this.$swal({
          type: "question",
          title: this.$tc("phrases.sellAsset", 0),
          text: this.$tc("phrases.sellAsset", 2, {
            cost:
              this.newAppliance.cost +
              this.$store.getters["settings/getMainSettings"].currency,
          }),
          showCancelButton: true,
          cancelButtonText: this.$tc("words.cancel"),
          confirmButtonText: this.$tc("words.sell"),
        }).then(async (result) => {
          if (result.value) {
            const appliance = this.applianceService.list.find(
              (x) => x.id === this.applianceTypeIndex,
            )
            try {
              let validator = await this.$validator.validateAll()
              if (validator) {
                if (this.checkIsForSHS(appliance)) {
                  const tariffName = `${this.selectedApplianceName}-${this.selectedDeviceSerialNumber}`
                  const currency =
                    this.$store.getters["settings/getMainSettings"].currency
                  const { data } = await this.tariffService.createNewShsTariff(
                    tariffName,
                    this.selectedDeviceSerialNumber,
                    this.minimumPayableAmount,
                    this.rateCost,
                    currency,
                  )
                  await this.tariffService.changeTariffForSpecificMeter(
                    this.selectedDeviceSerialNumber,
                    data.id,
                  )
                }

                let soldAppliance = await this.assetPersonService.saveAsset(
                  this.newAppliance.id,
                  this.personId,
                  this.newAppliance,
                  this.adminId,
                )
                this.alertNotify("success", this.$tc("phrases.sellAsset", 1))
                await this.$router.push(
                  "/sold-appliance-detail/" + soldAppliance.id,
                )
              }
            } catch (e) {
              this.alertNotify("error", e.message)
            }
          }
        })
      }
    },
    async getPerson() {
      this.person = await this.personService.getPerson(this.personId)
    },
    getRate(index, rateCount, cost) {
      if (index === parseInt(rateCount)) {
        return cost - (rateCount - 1) * Math.floor(cost / rateCount)
      } else {
        return Math.floor(cost / rateCount)
      }
    },
    checkDownPayment() {
      if (
        parseFloat(this.newAppliance.downPayment) >
        parseFloat(this.newAppliance.cost)
      ) {
        this.newAppliance.downPayment = 0
        this.alertNotify(
          "warn",
          "Down Payment is not bigger than Appliance Cost",
        )
      } else if (this.newAppliance.cost === this.newAppliance.downPayment) {
        this.newAppliance.rate = 0
        this.applianceRate = false
      } else {
        this.applianceRate = true
      }
      this.minimumPayableAmount = 0
      this.rateCost = 0
    },
    checkIsForSHS(appliance) {
      if (
        appliance.applianceType === APPLIANCE_TYPE_SHS_ID ||
        appliance.assetTypeName === APPLIANCE_TYPE_SHS_NAME
      ) {
        this.showSelectDevice = true
        this.newAppliance.isShs = true
        return true
      } else {
        this.showSelectDevice = false
        return false
      }
    },
    calculateRateCountsOnRateCostChange() {
      const remainingCost =
        parseFloat(this.newAppliance.cost) -
        parseFloat(this.newAppliance.downPayment)
      const thirtyDaysCost = Number(this.rateCost)

      if (thirtyDaysCost > remainingCost) {
        this.alertNotify(
          "warn",
          "Rate cost can not be bigger than remaining cost",
        )
        this.minimumPayableAmount = 0
        this.newAppliance.rate = 0
        return
      }
      if (thirtyDaysCost < 1 || typeof thirtyDaysCost !== "number") {
        this.minimumPayableAmount = 0
        this.newAppliance.rate = 0
        return
      }
      if (thirtyDaysCost < 30) {
        this.alertNotify("warn", "Rate cost can not be less than 30")
        this.minimumPayableAmount = 0
        this.newAppliance.rate = 0

        return
      }

      const dailyCost = thirtyDaysCost / 30
      const minimumAllowedAccessDayCount = 7
      this.minimumPayableAmount = Math.floor(
        dailyCost * minimumAllowedAccessDayCount,
      )
      this.newAppliance.rate = Math.floor(
        remainingCost / this.minimumPayableAmount,
      )
    },
  },
}
</script>

<style scoped></style>
