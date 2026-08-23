<template>
  <md-dialog
    :md-active="showSellApplianceModal"
    @update:mdActive="handleDialogActive"
    style="max-width: 50rem; margin: auto"
  >
    <md-dialog-title>{{ $tc("phrases.sellAppliance", 0) }}</md-dialog-title>
    <md-dialog-content
      style="overflow-y: auto"
      class="md-layout-item md-size-100"
    >
      <loader v-if="loading" />
      <p v-else-if="!assignedAppliances.length">
        {{ $tc("phrases.noAssignedAppliances") }}
      </p>
      <div v-else>
        <form data-vv-scope="sale-form" class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <md-field
              :class="{ 'md-invalid': errors.has('sale-form.customer') }"
            >
              <label>{{ $tc("words.customer") }}</label>
              <md-select
                name="customer"
                id="customer"
                v-model="selectedCustomerId"
                v-validate="'required'"
                @md-opened="focusCustomerSearchInput"
                @md-closed="resetCustomerSearch"
              >
                <div class="select-search-row" @click.stop @mousedown.stop>
                  <md-field md-inline>
                    <md-icon>search</md-icon>
                    <md-input
                      ref="customerSearchInput"
                      v-model="customerSearchTerm"
                      :placeholder="$tc('phrases.searchCustomer')"
                      @click.native.stop
                      @mousedown.native.stop
                      @keydown.native.stop
                    />
                  </md-field>
                </div>
                <md-option disabled v-if="isCustomerSearching">
                  Searching…
                </md-option>
                <md-option disabled v-else-if="!customerSelectOptions.length">
                  {{ customerSearchHint }}
                </md-option>
                <md-option
                  v-else
                  v-for="customer in customerSelectOptions"
                  :key="customer.id"
                  :value="customer.id"
                >
                  {{ customer.name }}
                </md-option>
              </md-select>
              <span class="md-error">
                {{ errors.first("sale-form.customer") }}
              </span>
            </md-field>
          </div>

          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field
              :class="{
                'md-invalid': errors.has('sale-form.assigned_appliance'),
              }"
            >
              <label for="assigned_appliance">
                {{ $tc("phrases.assignedAppliance") }}
              </label>
              <md-select
                name="assigned_appliance"
                id="assigned_appliance"
                v-model="selectedAssignedApplianceId"
                v-validate="'required'"
              >
                <md-option
                  v-for="assignedAppliance in assignedAppliances"
                  :key="assignedAppliance.id"
                  :value="assignedAppliance.id"
                >
                  {{ assignedAppliance.appliance.name }} —
                  {{ moneyFormat(assignedAppliance.cost) }}
                </md-option>
              </md-select>
              <span class="md-error">
                {{ errors.first("sale-form.assigned_appliance") }}
              </span>
            </md-field>
          </div>

          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field>
              <label for="cost">{{ $tc("words.cost") }}</label>
              <md-input id="cost" name="cost" :value="cost" readonly disabled />
            </md-field>
          </div>

          <div
            v-if="isDeviceSelectionRequired"
            class="md-layout-item md-size-100"
          >
            <md-field
              :class="{ 'md-invalid': errors.has('sale-form.device_serial') }"
            >
              <label>{{ $tc("phrases.selectDevice") }}</label>
              <md-select
                name="device_serial"
                v-model="selectedDeviceSerial"
                v-validate="'required'"
                @md-opened="focusDeviceSearchInput"
                @md-closed="resetDeviceSearch"
              >
                <div class="select-search-row" @click.stop @mousedown.stop>
                  <md-field md-inline>
                    <md-icon>search</md-icon>
                    <md-input
                      ref="deviceSearchInput"
                      v-model="deviceSearchTerm"
                      placeholder="Search by serial..."
                      @click.native.stop
                      @mousedown.native.stop
                      @keydown.native.stop
                    />
                  </md-field>
                </div>
                <md-option disabled v-if="isDeviceSearching">
                  Searching…
                </md-option>
                <md-option disabled v-else-if="!deviceSelectionList.length">
                  No available device found.
                </md-option>
                <md-option
                  v-else
                  v-for="device in deviceSelectionList"
                  :key="device.id"
                  :value="device.serial"
                >
                  {{ device.serial }}
                </md-option>
              </md-select>
              <span class="md-error">
                {{ errors.first("sale-form.device_serial") }}
              </span>
            </md-field>
          </div>
        </form>

        <md-tabs>
          <md-tab
            id="installment"
            @click="tabName = 'installment'"
            md-label="Installment"
          >
            <form data-vv-scope="installment-form" class="md-layout md-gutter">
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('installment-form.down_payment'),
                  }"
                >
                  <label for="down_payment">
                    {{ $tc("phrases.downPayment") }}
                  </label>
                  <md-input
                    type="number"
                    name="down_payment"
                    id="down_payment"
                    min="0"
                    v-model="sale.downPayment"
                    v-validate="'required|min_value:0|decimal'"
                    @keyup="checkDownPayment"
                  />
                  <span class="md-error">
                    {{ errors.first("installment-form.down_payment") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('installment-form.rate_type'),
                  }"
                >
                  <label for="rate_type">{{ $tc("phrases.rateType") }}</label>
                  <md-select
                    name="rate_type"
                    id="rate_type"
                    v-model="sale.rateType"
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
                    {{ errors.first("installment-form.rate_type") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('installment-form.tenure'),
                  }"
                >
                  <label for="tenure">{{ $tc("phrases.ratesCount") }}</label>
                  <md-input
                    type="number"
                    name="tenure"
                    id="tenure"
                    min="1"
                    v-model="sale.tenure"
                    v-validate="'required|integer|min_value:1'"
                    @input="onInstallmentCountInput"
                  />
                  <span class="md-error">
                    {{ errors.first("installment-form.tenure") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="installment_amount">
                    {{ $tc("phrases.installmentAmount") }}
                  </label>
                  <md-input
                    type="number"
                    name="installment_amount"
                    id="installment_amount"
                    min="1"
                    v-model="installmentAmount"
                    @input="onInstallmentAmountInput"
                    @blur="settleInstallmentAmount"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-datepicker
                  name="first_payment_date"
                  md-immediately
                  v-model="sale.firstPaymentDate"
                  :md-close-on-blur="false"
                >
                  <label for="first_payment_date">
                    {{ $tc("phrases.firstPaymentDate") }}
                  </label>
                </md-datepicker>
              </div>
              <div
                v-if="installmentPlan"
                class="md-layout-item md-size-100 installment-plan"
              >
                <strong>
                  {{
                    $tc("phrases.installmentPlan", 1, {
                      count: installmentPlan.count,
                      amount: moneyFormat(installmentPlan.amount),
                    })
                  }}
                </strong>
                <span v-if="installmentPlan.finalAmount !== null">
                  {{
                    $tc("phrases.finalInstallment", 1, {
                      amount: moneyFormat(installmentPlan.finalAmount),
                    })
                  }}
                </span>
                <span v-if="installmentPlan.lastDueDate">
                  {{
                    $tc("phrases.lastPaymentDue", 1, {
                      date: installmentPlan.lastDueDate,
                    })
                  }}
                </span>
              </div>
            </form>
          </md-tab>

          <md-tab
            id="energy-service"
            @click="tabName = 'energy-service'"
            md-label="Energy as a Service"
          >
            <form
              data-vv-scope="energy-service-form"
              class="md-layout md-gutter"
            >
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="eaas_down_payment">
                    {{ $tc("phrases.downPayment") }}
                  </label>
                  <md-input
                    type="number"
                    name="eaas_down_payment"
                    id="eaas_down_payment"
                    min="0"
                    v-model="sale.downPayment"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has(
                      'energy-service-form.price_per_day',
                    ),
                  }"
                >
                  <label for="price_per_day">
                    {{ $tc("phrases.pricePerDay") }}
                  </label>
                  <md-input
                    type="number"
                    name="price_per_day"
                    id="price_per_day"
                    min="0"
                    v-model="sale.pricePerDay"
                    v-validate="'required|min_value:0|integer'"
                  />
                  <span class="md-error">
                    {{ errors.first("energy-service-form.price_per_day") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="minimum_payable_amount">
                    {{ $tc("phrases.minimumPayableAmount", 0) }}
                  </label>
                  <md-input
                    type="number"
                    name="minimum_payable_amount"
                    id="minimum_payable_amount"
                    min="0"
                    v-model="sale.minimumPayableAmount"
                  />
                </md-field>
              </div>
              <div class="md-layout-item md-size-100">
                <p class="eaas-description">
                  {{ $tc("phrases.eaasDescription") }}
                </p>
              </div>
            </form>
          </md-tab>
        </md-tabs>
      </div>
    </md-dialog-content>
    <md-dialog-actions>
      <md-button type="button" @click="hide">
        {{ $tc("words.cancel") }}
      </md-button>
      <md-button
        type="button"
        class="md-primary md-raised"
        :disabled="loading || !assignedAppliances.length"
        @click="sellAppliance"
      >
        {{ $tc("words.sell") }}
      </md-button>
    </md-dialog-actions>
  </md-dialog>
</template>

<script>
import moment from "moment"

import { computeRateAmount } from "@/Helpers/applianceRates.js"
import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { AgentAssignedApplianceService } from "@/services/AgentAssignedApplianceService.js"
import { AgentSoldApplianceService } from "@/services/AgentSoldApplianceService.js"
import { DeviceService } from "@/services/DeviceService.js"
import { PersonService } from "@/services/PersonService.js"
import Loader from "@/shared/Loader.vue"

const debounce = require("debounce")

// These are fixed values in the database
const APPLIANCE_TYPE_SHS_ID = 1
const APPLIANCE_TYPE_E_BIKE_ID = 2
const MINIMUM_CUSTOMER_SEARCH_LENGTH = 3

const emptySale = () => ({
  downPayment: 0,
  rateType: "monthly",
  tenure: null,
  firstPaymentDate: null,
  minimumPayableAmount: null,
  pricePerDay: null,
})

export default {
  name: "SellAssignedAppliance",
  mixins: [currency, notify],
  components: { Loader },
  props: {
    agentId: {
      required: true,
    },
    showSellApplianceModal: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      agentSoldApplianceService: new AgentSoldApplianceService(this.agentId),
      assignedApplianceService: new AgentAssignedApplianceService(),
      deviceService: new DeviceService(),
      personService: new PersonService(),
      assignedAppliances: [],
      customerOptions: [],
      customerSearchTerm: "",
      isCustomerSearching: false,
      selectedCustomer: null,
      selectedCustomerId: null,
      selectedAssignedApplianceId: null,
      selectedDeviceSerial: null,
      deviceSelectionList: [],
      deviceSearchTerm: "",
      isDeviceSearching: false,
      tabName: "installment",
      installmentAmount: null,
      loading: false,
      sale: emptySale(),
    }
  },
  computed: {
    selectedAssignedAppliance() {
      return (
        this.assignedAppliances.find(
          (assignedAppliance) =>
            assignedAppliance.id === this.selectedAssignedApplianceId,
        ) ?? null
      )
    },
    cost() {
      return this.selectedAssignedAppliance?.cost ?? 0
    },
    isEnergyService() {
      return this.tabName === "energy-service"
    },
    remainingCost() {
      return Math.max(Number(this.cost) - Number(this.sale.downPayment || 0), 0)
    },
    // the schedule the backend will actually generate: an even split with the
    // final installment absorbing the rounding remainder
    installmentPlan() {
      const count = parseInt(this.sale.tenure)
      if (!count || count < 1 || this.remainingCost <= 0) return null

      const amount = computeRateAmount(1, count, this.remainingCost)
      const finalAmount = computeRateAmount(count, count, this.remainingCost)

      return {
        count,
        amount,
        finalAmount: finalAmount === amount ? null : finalAmount,
        lastDueDate: this.sale.firstPaymentDate
          ? moment(this.sale.firstPaymentDate)
              .add(count, this.sale.rateType === "weekly" ? "weeks" : "months")
              .format("DD MMM YYYY")
          : null,
      }
    },
    // a later search must not drop the already picked customer out of the
    // option list, or md-select renders the field as if nothing were selected
    customerSelectOptions() {
      if (!this.selectedCustomer) return this.customerOptions
      const isInResults = this.customerOptions.some(
        (customer) => customer.id === this.selectedCustomer.id,
      )
      return isInResults
        ? this.customerOptions
        : [this.selectedCustomer, ...this.customerOptions]
    },
    customerSearchHint() {
      return this.customerSearchTerm.length < MINIMUM_CUSTOMER_SEARCH_LENGTH
        ? this.$tc("phrases.searchCustomer")
        : `No customer matching "${this.customerSearchTerm}" was found.`
    },
    isDeviceSelectionRequired() {
      const applianceTypeId =
        this.selectedAssignedAppliance?.appliance?.appliance_type_id
      return (
        applianceTypeId === APPLIANCE_TYPE_SHS_ID ||
        applianceTypeId === APPLIANCE_TYPE_E_BIKE_ID
      )
    },
  },
  watch: {
    showSellApplianceModal(visible) {
      if (visible) this.getAssignedAppliances()
    },
    async selectedAssignedApplianceId() {
      this.selectedDeviceSerial = null
      this.deviceSearchTerm = ""
      this.deviceSelectionList = []
      this.onInstallmentCountInput()
      if (this.isDeviceSelectionRequired) {
        await this.loadDevicesForAppliance()
      }
    },
    customerSearchTerm: debounce(function () {
      this.searchCustomers(this.customerSearchTerm.trim())
    }, 400),
    selectedCustomerId(id) {
      const selected = this.customerOptions.find(
        (customer) => customer.id === id,
      )
      if (selected) this.selectedCustomer = selected
    },
    deviceSearchTerm: debounce(function () {
      if (!this.isDeviceSelectionRequired) return
      this.loadDevicesForAppliance(this.deviceSearchTerm.trim() || null)
    }, 300),
  },
  methods: {
    handleDialogActive(visible) {
      if (!visible) this.hide()
    },
    hide() {
      this.$emit("hideModal")
    },
    async getAssignedAppliances() {
      this.loading = true
      try {
        this.assignedAppliances =
          await this.assignedApplianceService.getAssignedAppliances(
            this.agentId,
          )
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
      }
    },
    async searchCustomers(term) {
      if (!term || term.length < MINIMUM_CUSTOMER_SEARCH_LENGTH) {
        this.customerOptions = []
        return
      }
      this.isCustomerSearching = true
      try {
        const { status, data } = await this.personService.searchPerson({
          params: { term: term, paginate: 0 },
        })
        if (status !== 200) return
        this.customerOptions = data.data.map((person) => ({
          id: person.id,
          name: `${person.name} ${person.surname}`,
        }))
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.isCustomerSearching = false
      }
    },
    focusCustomerSearchInput() {
      this.$nextTick(() => {
        const input = this.$refs.customerSearchInput
        if (input && typeof input.focus === "function") input.focus()
      })
    },
    resetCustomerSearch() {
      this.customerSearchTerm = ""
    },
    onInstallmentCountInput() {
      this.installmentAmount = this.installmentPlan
        ? this.installmentPlan.amount
        : null
    },
    onInstallmentAmountInput() {
      const amount = Number(this.installmentAmount)
      if (!amount || amount < 1 || this.remainingCost <= 0) {
        this.sale.tenure = null
        return
      }
      // the even split floors, so ceil() alone lands one installment too high and
      // the count drifts every time this field is touched; step back down while a
      // shorter plan still keeps each installment at or under the amount asked for
      let count = Math.max(1, Math.ceil(this.remainingCost / amount))
      while (
        count > 1 &&
        computeRateAmount(1, count - 1, this.remainingCost) <= amount
      ) {
        count--
      }
      this.sale.tenure = count
    },
    // the count is a whole number, so the amount that was typed is rarely the
    // amount that gets charged; settle the field to the real one once editing stops
    settleInstallmentAmount() {
      if (this.installmentPlan) {
        this.installmentAmount = this.installmentPlan.amount
      }
    },
    checkDownPayment() {
      if (parseFloat(this.sale.downPayment) > parseFloat(this.cost)) {
        this.sale.downPayment = 0
        this.alertNotify("warn", "Down payment is bigger than appliance cost")
      }
      this.onInstallmentCountInput()
    },
    focusDeviceSearchInput() {
      this.$nextTick(() => {
        const input = this.$refs.deviceSearchInput
        if (input && typeof input.focus === "function") input.focus()
      })
    },
    resetDeviceSearch() {
      this.deviceSearchTerm = ""
    },
    async loadDevicesForAppliance(serial = null) {
      this.isDeviceSearching = true
      try {
        await this.deviceService.getAvailableDevicesForAppliance(
          this.selectedAssignedAppliance.appliance.id,
          serial,
        )
        this.deviceSelectionList = this.deviceService.list.map((device) => ({
          id: device.id,
          serial: device.deviceSerial,
        }))
      } catch {
        this.deviceSelectionList = []
      } finally {
        this.isDeviceSearching = false
      }
    },
    async validateSaleForm() {
      const scope = this.isEnergyService
        ? "energy-service-form"
        : "installment-form"
      const results = await Promise.all([
        this.$validator.validateAll("sale-form"),
        this.$validator.validateAll(scope),
      ])
      if (results.includes(false)) return false

      if (!this.isEnergyService && !this.sale.firstPaymentDate) {
        this.alertNotify("error", this.$tc("phrases.firstPaymentDate"))
        return false
      }
      return true
    },
    buildSaleParams() {
      return {
        agentId: Number(this.agentId),
        personId: this.selectedCustomerId,
        agentAssignedApplianceId: this.selectedAssignedApplianceId,
        paymentType: this.isEnergyService ? "energy_service" : "installment",
        downPayment: Number(this.sale.downPayment) || 0,
        rateType: this.isEnergyService ? null : this.sale.rateType,
        tenure: this.isEnergyService ? null : Number(this.sale.tenure),
        firstPaymentDate: this.isEnergyService
          ? null
          : moment(this.sale.firstPaymentDate).format("YYYY-MM-DD"),
        minimumPayableAmount: this.isEnergyService
          ? Number(this.sale.minimumPayableAmount) || null
          : null,
        pricePerDay: this.isEnergyService
          ? Number(this.sale.pricePerDay) || null
          : null,
        deviceSerial: this.selectedDeviceSerial,
      }
    },
    async sellAppliance() {
      if (!(await this.validateSaleForm())) return

      const confirmation = await this.$swal({
        type: "question",
        title: this.$tc("phrases.sellAppliance", 0),
        text: this.isEnergyService
          ? this.$tc("phrases.confirmEaas")
          : this.$tc("phrases.sellAppliance", 2, {
              cost: this.moneyFormat(this.cost),
            }),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.sell"),
      })
      if (!confirmation.value) return

      this.loading = true
      try {
        await this.agentSoldApplianceService.sellAppliance(
          this.buildSaleParams(),
        )
        this.alertNotify("success", this.$tc("phrases.sellAppliance", 1))
        this.resetForm()
        this.$emit("applianceSold")
      } catch (e) {
        // the shared error helper throws from its own constructor, so a rejected
        // request arrives here rather than as a returned value
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
      }
    },
    resetForm() {
      this.customerSearchTerm = ""
      this.customerOptions = []
      this.selectedCustomer = null
      this.selectedCustomerId = null
      this.selectedAssignedApplianceId = null
      this.selectedDeviceSerial = null
      this.deviceSelectionList = []
      this.deviceSearchTerm = ""
      this.tabName = "installment"
      this.installmentAmount = null
      this.sale = emptySale()
      this.$validator.reset()
    },
  },
}
</script>

<style scoped lang="scss">
.eaas-description {
  font-size: 0.875rem;
  color: #555;
  line-height: 1.5;
}

.installment-plan {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem 1rem;
  padding: 0.25rem 0 0.75rem;
  font-size: 0.9rem;
  color: #555;
}

.select-search-row {
  position: sticky;
  top: 0;
  z-index: 1;
  padding: 0 1rem;
  background: #fff;
  border-bottom: 1px solid #e0e0e0;
}
</style>
