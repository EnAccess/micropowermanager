<template>
  <div>
    <!-- Consent Statistics Overview -->
    <widget color="primary" title="Customer Consent Overview">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">
          <div class="stat-card">
            <span class="stat-number">{{ stats.total_customers }}</span>
            <span class="stat-label">Total Customers</span>
          </div>
        </div>
        <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">
          <div class="stat-card stat-success">
            <span class="stat-number">{{ stats.opted_in }}</span>
            <span class="stat-label">Opted In (Active)</span>
          </div>
        </div>
        <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">
          <div class="stat-card stat-warning">
            <span class="stat-number">{{ stats.opted_out }}</span>
            <span class="stat-label">Opted Out</span>
          </div>
        </div>
        <div class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100">
          <div class="stat-card stat-info">
            <span class="stat-number">{{ stats.pending }}</span>
            <span class="stat-label">Pending / Unset</span>
          </div>
        </div>
      </div>
    </widget>

    <!-- Clean Customer WhatsApp Consent Management Table -->
    <widget color="primary" title="Customer WhatsApp Consent Management" style="margin-top: 1.5rem">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <p class="description-text">
            Select customers using checkboxes to grant or revoke WhatsApp messaging consent in batch, or toggle individual customer consent directly in the table.
          </p>

          <!-- Clean Search & Filter Controls -->
          <div class="filter-bar md-layout md-gutter md-alignment-center-space-between">
            <div class="md-layout-item md-size-60 md-small-size-100">
              <md-field md-clearable>
                <md-icon>search</md-icon>
                <label>Search customers by name or phone...</label>
                <md-input
                  v-model="searchTerm"
                  @keydown.enter="applyFilters"
                  @input="onSearchInput"
                />
              </md-field>
            </div>

            <div class="md-layout-item md-size-40 md-small-size-100">
              <md-field>
                <label>Filter by Status</label>
                <md-select v-model="statusFilter" @md-selected="applyFilters">
                  <md-option value="all">All Customers</md-option>
                  <md-option value="opted_in">Opted In Only</md-option>
                  <md-option value="opted_out">Opted Out Only</md-option>
                  <md-option value="pending">Pending / Not Set</md-option>
                </md-select>
              </md-field>
            </div>
          </div>

          <!-- Multi-Selected Action Bar -->
          <div
            v-if="selectedCustomerIds.length > 0"
            class="selected-actions-banner"
          >
            <div class="selected-count-badge">
              <md-icon style="color: #2e7d32">check_circle</md-icon>
              <span><strong>{{ selectedCustomerIds.length }}</strong> customer(s) selected</span>
            </div>
            <div class="selected-buttons">
              <md-button
                class="md-raised md-primary action-btn-optin"
                :disabled="loading"
                @click="confirmBatchAction(true)"
              >
                <md-icon>done_all</md-icon>
                Opt-in Selected ({{ selectedCustomerIds.length }})
              </md-button>
              <md-button
                class="md-raised md-accent action-btn-optout"
                :disabled="loading"
                @click="confirmBatchAction(false)"
              >
                <md-icon>block</md-icon>
                Opt-out Selected ({{ selectedCustomerIds.length }})
              </md-button>
              <md-button
                class="md-dense md-simple"
                @click="clearSelection"
              >
                Deselect All
              </md-button>
            </div>
          </div>

          <!-- Customer Listing Table (Clean layout without redundant consent badge column) -->
          <div class="table-wrapper">
            <md-table class="customer-optin-table">
              <md-table-row class="table-header-row">
                <md-table-head style="width: 50px; text-align: center">
                  <input
                    type="checkbox"
                    :checked="isAllPageSelected"
                    :disabled="customers.length === 0"
                    title="Select all on this page"
                    @change="toggleSelectAllPage"
                  />
                </md-table-head>
                <md-table-head style="width: 70px">ID</md-table-head>
                <md-table-head>Customer Name</md-table-head>
                <md-table-head>Phone Number</md-table-head>
                <md-table-head style="text-align: right; width: 170px">Consent Status</md-table-head>
              </md-table-row>

              <md-table-row
                v-for="customer in customers"
                :key="customer.id"
                :class="{ 'row-selected': isCustomerSelected(customer.id) }"
              >
                <md-table-cell style="text-align: center">
                  <input
                    type="checkbox"
                    :checked="isCustomerSelected(customer.id)"
                    @change="toggleCustomerSelection(customer)"
                  />
                </md-table-cell>
                <md-table-cell>{{ customer.id }}</md-table-cell>
                <md-table-cell>
                  <strong>{{ customer.name }}</strong>
                </md-table-cell>
                <md-table-cell>
                  <code>{{ customer.phone }}</code>
                </md-table-cell>
                <md-table-cell style="text-align: right">
                  <md-switch
                    v-model="customer.opted_in"
                    class="md-primary inline-switch"
                    :disabled="loading"
                    @change="toggleSingleCustomer(customer)"
                  >
                    <span :class="customer.opted_in ? 'status-text-opted-in' : 'status-text-opted-out'">
                      {{ customer.opted_in ? 'Opted In' : 'Opted Out' }}
                    </span>
                  </md-switch>
                </md-table-cell>
              </md-table-row>

              <!-- Empty State -->
              <md-table-row v-if="customers.length === 0 && !loading">
                <md-table-cell colspan="5" style="text-align: center; padding: 2rem">
                  <md-icon style="font-size: 2.5rem; color: #94a3b8">search_off</md-icon>
                  <p style="margin-top: 0.5rem; color: #64748b">
                    No customers found matching the search criteria.
                  </p>
                </md-table-cell>
              </md-table-row>
            </md-table>
          </div>

          <!-- Pagination Bar -->
          <div class="pagination-footer">
            <div class="pagination-info">
              Showing page {{ pagination.current_page }} of {{ pagination.last_page }}
              ({{ pagination.total }} total customers)
            </div>
            <div class="pagination-controls">
              <md-button
                class="md-dense md-raised"
                :disabled="pagination.current_page <= 1 || loading"
                @click="changePage(pagination.current_page - 1)"
              >
                Previous
              </md-button>
              <md-button
                class="md-dense md-raised"
                :disabled="pagination.current_page >= pagination.last_page || loading"
                @click="changePage(pagination.current_page + 1)"
              >
                Next
              </md-button>
            </div>
          </div>
        </div>
      </div>
    </widget>

    <!-- Confirmation Dialog for Multi-Customer Batch Actions -->
    <md-dialog :md-active.sync="showConfirmDialog" style="max-width: 32rem">
      <md-dialog-title>{{ confirmTitle }}</md-dialog-title>
      <md-dialog-content>
        <p>{{ confirmMessage }}</p>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button class="md-simple" @click="showConfirmDialog = false">
          Cancel
        </md-button>
        <md-button
          :class="['md-raised', targetBatchStatus ? 'md-primary' : 'md-accent']"
          :disabled="loading"
          @click="executeBatchAction"
        >
          Confirm
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { WhatsappService } from "../../services/WhatsappService.js"

import { notify } from "@/mixins/notify.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "WhatsappBusinessApiOptIn",
  components: { Widget },
  mixins: [notify],
  data() {
    return {
      whatsappService: new WhatsappService(),
      loading: false,
      customers: [],
      stats: {
        total_customers: 0,
        opted_in: 0,
        opted_out: 0,
        pending: 0,
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
      },
      searchTerm: "",
      searchTimeout: null,
      statusFilter: "all",
      selectedCustomerIds: [],
      selectedCustomerNames: [],
      showConfirmDialog: false,
      targetBatchStatus: true,
      confirmTitle: "",
      confirmMessage: "",
    }
  },
  computed: {
    isAllPageSelected() {
      if (this.customers.length === 0) return false
      return this.customers.every((c) => this.selectedCustomerIds.includes(c.id))
    },
  },
  mounted() {
    this.fetchStats()
    this.fetchCustomers(1)
  },
  methods: {
    async fetchStats() {
      try {
        const response = await this.whatsappService.getOptInStats()
        if (response.data && response.data.data) {
          this.stats = response.data.data
        }
      } catch (error) {
        // Silently handle stats error
      }
    },
    async fetchCustomers(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          status: this.statusFilter,
        }
        if (this.searchTerm && this.searchTerm.trim() !== "") {
          params.search = this.searchTerm.trim()
        }

        const response = await this.whatsappService.getOptIns(params)
        if (response.data && response.data.data) {
          // Normalize boolean values for v-model switch binding
          this.customers = response.data.data.map((c) => ({
            ...c,
            opted_in: Boolean(c.opted_in),
          }))
          if (response.data.meta) {
            this.pagination.current_page = response.data.meta.current_page
            this.pagination.last_page = response.data.meta.last_page
            this.pagination.total = response.data.meta.total
          }
        }
      } catch (error) {
        this.alertNotify("error", "Failed to load customers opt-in list")
      } finally {
        this.loading = false
      }
    },
    onSearchInput() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.applyFilters()
      }, 400)
    },
    applyFilters() {
      this.fetchCustomers(1)
    },
    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchCustomers(page)
      }
    },
    isCustomerSelected(id) {
      return this.selectedCustomerIds.includes(id)
    },
    toggleCustomerSelection(customer) {
      const idx = this.selectedCustomerIds.indexOf(customer.id)
      if (idx > -1) {
        this.selectedCustomerIds.splice(idx, 1)
        this.selectedCustomerNames = this.selectedCustomerNames.filter(
          (c) => c.id !== customer.id,
        )
      } else {
        this.selectedCustomerIds.push(customer.id)
        if (!this.selectedCustomerNames.some((c) => c.id === customer.id)) {
          this.selectedCustomerNames.push({
            id: customer.id,
            name: customer.name,
          })
        }
      }
    },
    toggleSelectAllPage(event) {
      const checked = event.target.checked
      this.customers.forEach((customer) => {
        const idx = this.selectedCustomerIds.indexOf(customer.id)
        if (checked && idx === -1) {
          this.selectedCustomerIds.push(customer.id)
          if (!this.selectedCustomerNames.some((c) => c.id === customer.id)) {
            this.selectedCustomerNames.push({
              id: customer.id,
              name: customer.name,
            })
          }
        } else if (!checked && idx > -1) {
          this.selectedCustomerIds.splice(idx, 1)
          this.selectedCustomerNames = this.selectedCustomerNames.filter(
            (c) => c.id !== customer.id,
          )
        }
      })
    },
    clearSelection() {
      this.selectedCustomerIds = []
      this.selectedCustomerNames = []
    },
    async toggleSingleCustomer(customer) {
      const newStatus = Boolean(customer.opted_in)
      this.loading = true
      try {
        const response = await this.whatsappService.updateOptIn(customer.id, {
          opted_in: newStatus,
        })
        if (response.data && response.data.data) {
          customer.opted_in = Boolean(response.data.data.opted_in)
          this.alertNotify(
            "success",
            `${customer.name} status updated to ${customer.opted_in ? "Opted In" : "Opted Out"}.`,
          )
          await this.fetchStats()
        }
      } catch (error) {
        // Revert switch on failure
        customer.opted_in = !newStatus
        this.alertNotify("error", "Failed to update customer status")
      } finally {
        this.loading = false
      }
    },
    confirmBatchAction(status) {
      if (this.selectedCustomerIds.length === 0) return
      this.targetBatchStatus = status
      const count = this.selectedCustomerIds.length
      if (status) {
        this.confirmTitle = `Opt-In ${count} Selected Customer(s)?`
        this.confirmMessage = `This will grant WhatsApp messaging consent for the ${count} selected customer(s). Do you want to proceed?`
      } else {
        this.confirmTitle = `Opt-Out ${count} Selected Customer(s)?`
        this.confirmMessage = `This will revoke WhatsApp messaging consent for the ${count} selected customer(s). Do you want to proceed?`
      }
      this.showConfirmDialog = true
    },
    async executeBatchAction() {
      this.showConfirmDialog = false
      this.loading = true
      try {
        const response = await this.whatsappService.bulkOptIn({
          person_ids: this.selectedCustomerIds,
          opted_in: this.targetBatchStatus,
        })
        const count =
          response.data && response.data.data
            ? response.data.data.count
            : this.selectedCustomerIds.length
        this.alertNotify(
          "success",
          `Successfully updated ${count} customer opt-in records.`,
        )
        this.clearSelection()
        await this.fetchStats()
        await this.fetchCustomers(this.pagination.current_page)
      } catch (error) {
        this.alertNotify("error", "Failed to update selected customers")
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.stat-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;

  .stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.85rem;
    font-weight: 500;
    color: #64748b;
  }

  &.stat-success .stat-number {
    color: #16a34a;
  }

  &.stat-warning .stat-number {
    color: #dc2626;
  }

  &.stat-info .stat-number {
    color: #0284c7;
  }
}

.description-text {
  color: #475569;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}

.filter-bar {
  margin-bottom: 0.5rem;
}

.selected-actions-banner {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 0.75rem 1.25rem;
  margin: 1rem 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;

  .selected-count-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: #166534;
  }

  .selected-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;

    .action-btn-optin {
      background-color: #16a34a !important;
      color: #ffffff !important;
    }

    .action-btn-optout {
      background-color: #dc2626 !important;
      color: #ffffff !important;
    }
  }
}

.table-wrapper {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  margin-top: 0.5rem;
}

.customer-optin-table {
  width: 100%;

  .table-header-row {
    background: #f8fafc;
  }

  .row-selected {
    background: #f0fdf4 !important;
  }

  input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
  }

  .inline-switch {
    margin: 0;
  }
}

.status-text-opted-in {
  color: #16a34a;
  font-weight: 600;
  font-size: 0.85rem;
}

.status-text-opted-out {
  color: #64748b;
  font-weight: 500;
  font-size: 0.85rem;
}

.pagination-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
  padding: 0.5rem 0;
  flex-wrap: wrap;
  gap: 1rem;

  .pagination-info {
    font-size: 0.85rem;
    color: #64748b;
  }

  .pagination-controls {
    display: flex;
    gap: 0.5rem;
  }
}
</style>
