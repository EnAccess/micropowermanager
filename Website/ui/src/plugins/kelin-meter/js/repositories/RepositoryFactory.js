import CredentialRepository from './CredentialRepository'
import PaginatorRepository from './PaginatorRepository'
import DailyConsumptionRepository from './DailyConsumptionRepository'
import MinutelyConsumptionRepository from './MinutelyConsumptionRepository'
import SettingRepository from './SettingRepository'
import SyncSettingRepository from './SyncSettingRepository'
import CustomerRepository from './CustomerRepository'
import MeterRepository from './MeterRepository'
import StatusRepository from './StatusRepository'

const repositories = {
    'credential': CredentialRepository,
    'paginate': PaginatorRepository,
    'daily': DailyConsumptionRepository,
    'minutely': MinutelyConsumptionRepository,
    'setting': SettingRepository,
    'syncSetting': SyncSettingRepository,
    'customer':CustomerRepository,
    'meter':MeterRepository,
    'status':StatusRepository,
}
export default {
    get: name => repositories[name]
}