<template>
  <div>
    <md-dialog
      :md-active.sync="showSellApplianceModal"
      style="max-width: 60rem; margin: auto"
    >
      <md-dialog-title>Sell Appliance</md-dialog-title>
      <md-dialog-content
        style="overflow-y: auto"
        class="md-layout-item md-size-100"
      >
        <div v-if="loading">
          <loader />
        </div>
        <div v-else>
          <md-tabs>
            <md-tab
              id="count-based"
              @click="tabName = 'count-based'"
              md-label="Installment Count Based"
            >
              <form
                data-vv-scope="count-based-form"
                class="md-layout md-gutter"
              >
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.appliance'),
                    }"
                  >
                    <label for="appliance">
                      {{ $tc("words.appliance") }}
                    </label>
                    <md-select
                      name="appliance"
                      id="appliance"
                      v-model="selectedApplianceId"
                      v-validate="'required'"
                    >
                      <md-option disabled value>
                        --{{ $tc("words.select") }}--
                      </md-option>
                      <md-option
                        :value="appliance.id"
                        v-for="appliance in applianceService.list"
                        :key="appliance.id"
                      >
                        {{ appliance.name }}
                      </md-option>
                    </md-select>

                    <span class="md-error">
                      {{ errors.first("count-based-form.appliance") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.cost'),
                    }"
                  >
                    <label for="cost">
                      {{ $tc("words.cost") }}
                    </label>
                    <md-input
                      type="number"
                      name="cost"
                      id="cost"
                      v-model="applianceService.appliance.cost"
                      @keyup="checkDownPayment"
                      v-validate="'required|decimal'"
                    />
                    <span class="md-error">
                      {{ errors.first("count-based-form.cost") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.down_payment'),
                    }"
                  >
                    <label for="down_payment">Down Payment</label>
                    <md-input
                      type="number"
                      name="down_payment"
                      id="down_payment"
                      min="0"
                      v-model="applianceService.appliance.downPayment"
                      v-validate="'required|min_value:0|decimal'"
                      @keyup="checkDownPayment"
                    />
                    <span class="md-error">
                      {{ errors.first("count-based-form.down_payment") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.rate_type'),
                    }"
                  >
                    <label for="rate_type">
                      {{ $tc("phrases.rateType") }}
                    </label>
                    <md-select
                      name="rate_type"
                      id="rate_type"
                      v-model="applianceService.appliance.rateType"
                      v-validate="'required'"
                    >
                      <md-option value="weekly">
                        {{ $tc("words.week", 2) }}
                      </md-option>
                      <md-option value="monthly">
                        {{ $tc("words.month", 2) }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("count-based-form.rate_type") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'count-based-form.installment_count',
                      ),
                    }"
                  >
                    <label for="rate">
                      {{ $tc("phrases.ratesCount") }}
                    </label>
                    <md-input
                      type="number"
                      name="installment_count"
                      id="installment_count"
                      v-model="applianceService.appliance.rate"
                      v-validate="'required|integer'"
                    />
                    <span class="md-error">
                      {{ errors.first("count-based-form.rate") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('count-based-form.address'),
                    }"
                  >
                    <label for="address">
                      {{ $tc("words.address") }}
                    </label>
                    <md-select
                      id="address"
                      name="address"
                      v-model="selectedAddressId"
                      v-validate="'required'"
                    >
                      <md-option
                        v-for="(adr, index) in person.addresses"
                        :key="index"
                        :value="adr.id"
                      >
                        {{ adr.city.name }}
                        {{ adr.street }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("count-based-form.address") }}
                    </span>
                  </md-field>
                </div>
              </form>
            </md-tab>
            <md-tab
              id="cost-based"
              @click="tabName = 'cost-based'"
              md-label="Installment Cost Based"
            >
              <form data-vv-scope="cost-based-form" class="md-layout md-gutter">
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.appliance'),
                    }"
                  >
                    <label for="appliance">
                      {{ $tc("words.appliance") }}
                    </label>
                    <md-select
                      name="appliance"
                      id="appliance"
                      v-model="selectedApplianceId"
                      v-validate="'required'"
                    >
                      <md-option disabled value>
                        --{{ $tc("words.select") }}--
                      </md-option>
                      <md-option
                        :value="appliance.id"
                        v-for="appliance in applianceService.list"
                        :key="appliance.id"
                      >
                        {{ appliance.name }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("cost-based-form.appliance") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.cost'),
                    }"
                  >
                    <label for="cost">
                      {{ $tc("words.cost") }}
                    </label>
                    <md-input
                      type="number"
                      name="cost"
                      id="cost"
                      v-model="applianceService.appliance.cost"
                      @keyup="checkDownPayment"
                      v-validate="'required|decimal'"
                    />
                    <span class="md-error">
                      {{ errors.first("cost-based-form.cost") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.down_payment'),
                    }"
                  >
                    <label for="down_payment">Down Payment</label>
                    <md-input
                      type="number"
                      name="down_payment"
                      id="down_payment"
                      v-validate="'required|min_value:0|decimal'"
                      v-model="applianceService.appliance.downPayment"
                      min="0"
                      @keyup="checkDownPayment"
                    />
                    <span class="md-error">
                      {{ errors.first("cost-based-form.down_payment") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.rate_type'),
                    }"
                  >
                    <label for="rate_type">
                      {{ $tc("phrases.rateType") }}
                    </label>
                    <md-select
                      name="rate_type"
                      id="rate_type"
                      v-model="applianceService.appliance.rateType"
                      v-validate="'required'"
                    >
                      <md-option value="weekly">
                        {{ $tc("words.week", 2) }}
                      </md-option>
                      <md-option value="monthly">
                        {{ $tc("words.month", 2) }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("cost-based-form.rate_type") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has(
                        'cost-based-form.installment_cost',
                      ),
                    }"
                  >
                    <label for="installment_cost">
                      {{
                        $tc("words.rateCost", 1, {
                          rateType: applianceService.appliance.rateType,
                        })
                      }}
                    </label>
                    <md-input
                      type="number"
                      name="installment_cost"
                      id="installment_cost"
                      @keyup="calculateRateCountsOnRateCostChange"
                      v-model="applianceService.appliance.rateCost"
                      v-validate="'required|decimal'"
                    />
                    <span class="md-error">
                      {{ errors.first("cost-based-form.installment_cost") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('cost-based-form.address'),
                    }"
                  >
                    <label for="address">
                      {{ $tc("words.address") }}
                    </label>
                    <md-select
                      id="address"
                      name="address"
                      v-model="selectedAddressId"
                      v-validate="'required'"
                    >
                      <md-option
                        v-for="(adr, index) in person.addresses"
                        :key="index"
                        :value="adr.id"
                      >
                        {{ adr.city.name }}
                        {{ adr.street }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first("cost-based-form.address") }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-100 md-small-size-100">
                  <md-field>
                    <label for="minimumPayableAmount">
                      {{
                        applianceService.appliance.rateType === "weekly"
                          ? $tc("phrases.minimumPayableAmount", 1)
                          : $tc("phrases.minimumPayableAmount", 2)
                      }}
                    </label>
                    <md-input
                      type="number"
                      id="minimumPayableAmount"
                      name="minimumPayableAmount"
                      v-model="minimumPayableAmount"
                      readonly
                      disabled
                    />
                  </md-field>
                </div>
              </form>
            </md-tab>
          </md-tabs>
          <div
            class="md-layout-item md-size-100 md-small-size-100"
            v-if="isDeviceSelectionRequired"
          >
            <md-field
              :class="{
                'md-invalid': errors.has($tc('phrases.selectDevice')),
              }"
            >
              <label>
                {{ $tc("phrases.selectDevice") }}
              </label>
              <md-select
                :name="$tc('phrases.selectDevice')"
                v-model="selectedDeviceSerial"
                v-validate="'required'"
              >
                <template v-if="!deviceSelectionList.length">
                  <md-option disabled>
                    <md-tooltip md-direction="top">
                      Consider changing the search term or create a suitable
                      device first.
                    </md-tooltip>
                    No available device found.
                  </md-option>
                </template>
                <template v-else>
                  <md-option
                    v-for="device in deviceSelectionList"
                    :key="device.id"
                    :value="device.serial"
                  >
                    {{ device.serial }}
                  </md-option>
                </template>
              </md-select>
              <span class="md-error">
                {{ errors.first($tc("phrases.selectDevice")) }}
              </span>
            </md-field>
          </div>
          <div v-if="applianceService.appliance.rate" style="padding: 1rem">
            <div
              style="
                font-size: 1rem;
                margin: 0;
                border-bottom: solid 1px #dedede;
              "
            >
              <div>
                Cost:
                {{ moneyFormat(applianceService.appliance.cost) }}
                <br />
              </div>
              <div style="margin-top: 10px">
                Down Payment :
                {{ moneyFormat(applianceService.appliance.downPayment) }}
                <br />
              </div>
              <div style="margin-top: 10px">
                Rates: {{ applianceService.appliance.rate }}
              </div>
            </div>
            <div v-if="showRates">
              <div
                v-for="x in parseInt(applianceService.appliance.rate)"
                :key="x"
              >
                <span v-if="x < 10" style="opacity: 0">0</span>
                {{ x }}&nbsp;-&nbsp;{{
                  moneyFormat(
                    getRate(
                      x,
                      applianceService.appliance.rate,
                      applianceService.appliance.cost -
                        applianceService.appliance.downPayment,
                    ),
                  )
                }}
              </div>
            </div>
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          v-if="showRatesButton"
          class="md-accent md-raised"
          @click="showRates = !showRates"
        >
          Show Rates Detail
        </md-button>
        <md-button
          type="button"
          class="md-primary md-raised"
          @click="saveAppliance"
        >
          {{ $tc("words.sell") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { currency, notify } from "@/mixins"
import { ApplianceService } from "@/services/ApplianceService"
import { AssetPersonService } from "@/services/AssetPersonService"
import { DeviceService } from "@/services/DeviceService"
import { mapGetters } from "vuex"
import Loader from "@/shared/Loader.vue"

const APPLIANCE_TYPE_SHS_ID = 1
const APPLIANCE_TYPE_E_BIKE_ID = 2
export default {
  name: "SellApplianceModal",
  mixins: [currency, notify],
  components: {
    Loader,
  },
  props: {
    showSellApplianceModal: {
      required: true,
      type: Boolean,
    },
    person: {
      required: true,
    },
  },
  data() {
    return {
      applianceService: new ApplianceService(),
      assetPersonService: new AssetPersonService(),
      deviceService: new DeviceService(),
      selectedApplianceId: null,
      deviceSelectionList: [],
      isDeviceSelectionRequired: false,
      minimumPayableAmount: 0,
      selectedDeviceSerial: null,
      showRates: false,
      loading: false,
      tabName: "count-based",
      selectedAddressId: null,
    }
  },
  beforeMount() {
    this.getApplianceList()
    this.deviceService.getDevices()
  },
  methods: {
    async getApplianceList() {
      try {
        await this.applianceService.getAppliances()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveAppliance() {
      const formName = `${this.tabName}-form`
      const validator = await this.$validator.validateAll(formName)
      if (!validator) return
      const appliance = this.applianceService.list.find(
        (x) => x.id === this.applianceService.appliance.id,
      )
      const isDeviceBindingRequired = this.isDeviceBindingRequired(appliance)
      if (isDeviceBindingRequired && !this.selectedDeviceSerial) {
        this.alertNotify("error", "Please select a device")
        return
      }
      this.$swal({
        type: "question",
        title: this.$tc("phrases.sellAsset", 0),
        text: this.$tc("phrases.sellAsset", 2, {
          cost: this.moneyFormat(this.applianceService.appliance.cost),
        }),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.sell"),
      }).then(async (result) => {
        if (result.value) {
          try {
            this.loading = true
            const points = isDeviceBindingRequired
              ? await this.getGeoPointsForAppliance()
              : null
            const soldApplianceParams = {
              id: this.applianceService.appliance.id,
              personId: this.person.id,
              ...this.applianceService.appliance,
              points: points,
              userId: this.user.id,
              deviceSerial: this.selectedDeviceSerial,
              address: this.person.addresses.find(
                (x) => x.id === this.selectedAddressId,
              ),
            }
            const soldAppliance =
              await this.assetPersonService.sellAppliance(soldApplianceParams)
            this.alertNotify("success", this.$tc("phrases.sellAsset", 1))
            await this.$router.push(
              "/sold-appliance-detail/" + soldAppliance.id,
            )
          } catch (e) {
            console.log(e)
            this.alertNotify("error", e.message)
          }
          this.loading = false
        }
      })
    },
    async getGeoPointsForAppliance() {
      const address = this.person.addresses.find(
        (x) => x.id === this.selectedAddressId,
      )

      return address.geo?.points || address.city?.location?.points
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
        parseFloat(this.applianceService.appliance.downPayment) >
        parseFloat(this.applianceService.appliance.cost)
      ) {
        this.applianceService.appliance.downPayment = 0
        this.alertNotify(
          "warn",
          "Down Payment is not bigger than Appliance Cost",
        )
      } else if (
        this.applianceService.appliance.cost ===
        this.applianceService.appliance.downPayment
      ) {
        this.applianceService.appliance.rate = 0
      }
      this.minimumPayableAmount = 0
      this.rateCost = 0
    },
    calculateRateCountsOnRateCostChange() {
      const remainingCost =
        parseFloat(this.applianceService.appliance.cost) -
        parseFloat(this.applianceService.appliance.downPayment)
      const installmentCost = Number(this.applianceService.appliance.rateCost)
      if (installmentCost > remainingCost) {
        this.alertNotify(
          "warn",
          "Rate cost can not be bigger than remaining cost",
        )
        this.minimumPayableAmount = 0
        this.applianceService.appliance.rate = 0
        return
      }
      if (installmentCost < 1 || typeof installmentCost !== "number") {
        this.minimumPayableAmount = 0
        this.applianceService.appliance.rate = 0
        return
      }
      this.minimumPayableAmount = Math.floor(installmentCost)
      this.applianceService.appliance.rate = Math.floor(
        remainingCost / this.minimumPayableAmount,
      )
    },
    isDeviceBindingRequired(appliance) {
      return (
        appliance.assetTypeId === APPLIANCE_TYPE_SHS_ID ||
        appliance.assetTypeId === APPLIANCE_TYPE_E_BIKE_ID
      )
    },
  },
  computed: {
    ...mapGetters({
      settings: "settings/getMainSettings",
      user: "auth/getAuthenticateUser",
    }),
    showRatesButton() {
      return this.applianceService.appliance.rate > 1
    },
  },
  watch: {
    async selectedApplianceId() {
      this.applianceService.appliance.id = this.selectedApplianceId
      const availableDevices = this.deviceService.list.filter(
        (device) => !device.person,
      )
      const appliance = this.applianceService.list.find(
        (x) => x.id === this.applianceService.appliance.id,
      )
      if (this.isDeviceBindingRequired(appliance)) {
        this.isDeviceSelectionRequired = true
        this.deviceSelectionList = availableDevices
          .filter((device) => {
            switch (appliance.assetTypeId) {
              case APPLIANCE_TYPE_SHS_ID:
                return (
                  device.deviceType === "solar_home_system" &&
                  device.device.assetId === this.selectedApplianceId
                )
              case APPLIANCE_TYPE_E_BIKE_ID:
                return (
                  device.deviceType === "e_bike" &&
                  device.device.assetId === this.selectedApplianceId
                )
              default:
                return false
            }
          })
          .map((device) => {
            return {
              id: device.id,
              serial: device.deviceSerial,
            }
          })
      } else {
        this.isDeviceSelectionRequired = false
        this.deviceSelectionList = []
      }
    },
  },
}
</script>

<style scoped></style>
