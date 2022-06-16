import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class AgentAssignedApplianceService {
    constructor () {
        this.repository = Repository.get('assignedAppliance')
        this.list = []
        this.assignedAppliance = {
            id: null,
            agentId: null,
            personId: null,
            applianceTypeId: null,
            applianceType: null,
            cost: null
        }
    }

    fromJson (data) {
        this.assignedAppliance = {
            id: data.id,
            personId: data.person_id,
            applianceTypeId: data.appliance_type_id,
            cost: data.cost,
            applianceType: data.appliance_type.name
        }

        return this.assignedAppliance

    }

    updateList (data) {
        this.list = data.map(appliance => {
            return this.fromJson(appliance)
        })
        return this.list
    }

    async getAssignedAppliances (agentId) {
        try {
            let response = await this.repository.list(agentId)
            if (response.status === 200) {
                let list = response.data.data

                this.list = this.updateList(list)
                return this.list
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async assignAppliance (newAppliance, userId, AgentId) {
        try {
            let assignAppliancePM = {
                agent_id: AgentId,
                user_id: userId,
                appliance_type_id: newAppliance.id,
                cost: newAppliance.cost
            }
            let response = await this.repository.create(assignAppliancePM)
            if (response.status === 200 || response.status === 201) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
