import CredentialRepository from './CredentialRepository'
import CustomerRepository from './CustomerRepository'
import MeterModelRepository from './MeterModelRepository'
import TariffRepository from './TariffRepository'
import PaginatorRepository from './PaginatorRepository'
import SiteRepository from './SiteRepository'
import SettingRepository from './SettingRepository'
import SmsSettingRepository from './SmsSettingRepository'
import SyncSettingRepository from './SyncSettingRepository'
import SmsBodiesRepository from './SmsBodiesRepository'
import SmsVariableDefaultValueRepository from './SmsVariableDefaultValueRepository'
import SalesAccountRepository from './SalesAccountRepository'
import SmFeedbackWordRepository from './SmFeedbackWordRepository'
const repositories = {
    'credential':CredentialRepository,
    'customer':CustomerRepository,
    'meterModel':MeterModelRepository,
    'tariff':TariffRepository,
    'paginate':PaginatorRepository,
    'site':SiteRepository,
    'setting': SettingRepository,
    'smsSetting': SmsSettingRepository,
    'syncSetting': SyncSettingRepository,
    'smsBodies':SmsBodiesRepository,
    'smsVariableDefaultValue':SmsVariableDefaultValueRepository,
    'salesAccount':SalesAccountRepository,
    'feedBackWord':SmFeedbackWordRepository
}
export default {
    get: name => repositories[name]
}
