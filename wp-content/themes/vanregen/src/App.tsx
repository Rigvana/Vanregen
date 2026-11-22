import { Fragment, useEffect, useState, type FormEvent } from 'react';
import {
  ArrowRight,
  ArrowUpRight,
  Battery,
  Check,
  CircuitBoard,
  ChevronDown,
  ClipboardCheck,
  Cpu,
  Database,
  FileCheck2,
  Factory,
  HardDrive,
  IndianRupee,
  Leaf,
  Laptop,
  Mail,
  MapPin,
  Menu,
  Phone,
  Printer,
  PackageCheck,
  Recycle,
  Server,
  Search,
  ShieldCheck,
  Sprout,
  X,
  type LucideIcon,
} from 'lucide-react';

type RenderedText = { rendered: string };
type WordPressPage = {
  slug: string;
  title: RenderedText;
  content: RenderedText;
  excerpt: RenderedText;
  link: string;
  meta?: Record<string, string | string[]>;
};
type WordPressItem = {
  id: number;
  slug: string;
  title: RenderedText;
  content: RenderedText;
  excerpt: RenderedText;
  link: string;
  meta?: Record<string, string | string[]>;
  _embedded?: { 'wp:featuredmedia'?: Array<{ source_url: string; alt_text: string }> };
};
type ServiceDirectoryItem = {
  title: string;
  excerpt: string;
  icon: LucideIcon;
  image: string;
  slug: string;
  link: string;
  products?: string[];
};
type NavigationItem = {
  id: string | number;
  title: string;
  url: string;
  target?: string;
  children?: NavigationItem[];
};
type SiteConfig = {
  restUrl: string;
  restNonce: string;
  pageSlug: string;
  postType?: string;
  isHome?: boolean;
  siteUrl: string;
  logoUrl?: string;
  pageUrls: Record<string, string>;
  navigation?: NavigationItem[];
  siteCopy?: Record<string, string>;
};
type InquiryProduct = {
  product: string;
  quantity: string;
};
type InquiryServiceSelection = {
  service: string;
  products: InquiryProduct[];
};
type Inquiry = {
  name: string;
  company: string;
  email: string;
  phone: string;
  services: InquiryServiceSelection[];
  message: string;
  consent: boolean;
  website: string;
};

declare global {
  interface Window {
    vanRegenConfig?: SiteConfig;
  }
}

const pageLabels: Record<string, string> = {
  home: 'Home',
  about: 'About',
  'about-us': 'About',
  services: 'Services',
  'e-waste-recycling': 'E-Waste',
  'battery-recycling': 'Battery',
  'industrial-dismantling': 'Industrial Dismantling',
  'industrial-demolition-dismantling': 'Industrial Dismantling',
  'port-crane-dismantling': 'Port Cranes',
  sustainability: 'Sustainability',
  projects: 'Projects',
  contact: 'Contact',
  'contact-quote': 'Request a Quote',
};

const serviceFallbacks: ServiceDirectoryItem[] = [
  { title: 'E-Waste Recycling', excerpt: 'Responsible collection, segregation, dismantling, material recovery and recycling for obsolete electronic equipment.', icon: Recycle, image: '', slug: 'e-waste-recycling', link: '' },
  { title: 'Secure Data Destruction & IT Asset Sanitisation', excerpt: 'Structured data erasure, degaussing, physical destruction, secure handling and documentation for data-bearing equipment.', icon: ShieldCheck, image: '', slug: 'data-destruction', link: '' },
  { title: 'IT Asset Disposition & Buyback Solutions', excerpt: 'Secure assessment, collection, data sanitization, refurbishment, resale and responsible recycling for retired IT assets.', icon: Cpu, image: '', slug: 'itad-asset-buyback', link: '' },
  { title: 'Extended Producer Responsibility (EPR) Documentation', excerpt: 'Simplifying EPR compliance through organized registration support, records, certificates, reporting and audit preparation.', icon: FileCheck2, image: '', slug: 'epr-documentation', link: '' },
];
const serviceIcons: Record<string, LucideIcon> = {
  'e-waste-recycling': Recycle,
  'data-destruction': ShieldCheck,
  'itad-asset-buyback': Cpu,
  'epr-documentation': FileCheck2,
};
const serviceArtLabels: Record<string, string> = {
  'e-waste-recycling': 'MATERIAL RECOVERY',
  'data-destruction': 'SECURE SANITISATION',
  'itad-asset-buyback': 'ASSET LIFECYCLE',
  'epr-documentation': 'COMPLIANCE RECORDS',
};
const serviceShortNames: Record<string, string> = {
  'e-waste-recycling': 'E-Waste',
  'data-destruction': 'Data Destruction',
  'itad-asset-buyback': 'ITAD & Buyback',
  'epr-documentation': 'EPR Documentation',
};
const serviceQuoteOrder = [
  'e-waste-recycling',
  'itad-asset-buyback',
  'data-destruction',
  'epr-documentation',
];

const api = window.vanRegenConfig;
const knownPageSlugs = new Set([
  'home', 'about', 'about-us', 'services', 'e-waste-recycling', 'battery-recycling',
  'industrial-dismantling', 'industrial-demolition-dismantling', 'port-crane-dismantling',
  'sustainability', 'projects', 'contact', 'contact-quote',
]);
const servicePageSlugs: Record<string, string> = {
  'e-waste-recycling': 'e-waste-recycling',
  'battery-recycling': 'battery-recycling',
  'industrial-demolition-dismantling': 'industrial-demolition-dismantling',
  'port-crane-dismantling': 'port-crane-dismantling',
};

type ServiceFlow = {
  eyebrow: string;
  heading: string;
  summary: string;
  steps: Array<{ title: string; shortLabel: string; description: string; icon: LucideIcon }>;
};

const serviceFlows: Record<string, ServiceFlow> = {
  'e-waste-recycling': {
    eyebrow: 'OUR E-WASTE RECYCLING PROCESS',
    heading: 'Responsible recycling. Sustainable future.',
    summary: 'Give obsolete electronics a second life through systematic collection, dismantling, material recovery and responsible disposal.',
    steps: [
      { title: 'Collection & Transportation', shortLabel: 'Collect', description: 'Collect obsolete computers, laptops, servers, IT equipment, and other electronic assets from businesses and collection points.', icon: PackageCheck },
      { title: 'Inspection & Segregation', shortLabel: 'Inspect', description: 'Inspect, categorize, and separate collected materials by equipment type, composition, and recycling potential.', icon: Search },
      { title: 'Dismantling & Processing', shortLabel: 'Dismantle', description: 'Carefully dismantle equipment to separate circuit boards, cables, metals, plastics, batteries, and reusable components.', icon: Factory },
      { title: 'Material Recovery', shortLabel: 'Recover', description: 'Process valuable materials such as copper, aluminium, iron, and other recoverable resources for manufacturing reuse.', icon: Recycle },
      { title: 'Responsible Recycling & Disposal', shortLabel: 'Recycle', description: 'Direct non-recyclable and hazardous fractions to appropriate authorized treatment and disposal facilities in line with applicable regulations.', icon: Leaf },
    ],
  },
  'data-destruction': {
    eyebrow: 'OUR DATA DESTRUCTION & SANITISATION SERVICES',
    heading: 'Your data. Your security. Our responsibility.',
    summary: 'Select a service to explore controlled erasure, degaussing, physical destruction, asset sanitisation, reporting and secure handling.',
    steps: [
      { title: 'Certified Data Erasure', shortLabel: 'Erasure', description: 'Use appropriate sanitisation techniques to overwrite information on supported HDDs and SSDs according to the selected sanitisation standard.', icon: Database },
      { title: 'Degaussing', shortLabel: 'Degauss', description: 'Process compatible magnetic media using suitable degaussing equipment. This method is not suitable for SSDs or other non-magnetic storage.', icon: HardDrive },
      { title: 'Physical Data Destruction', shortLabel: 'Destruction', description: 'Use specialized shredding or crushing equipment for storage devices that require irreversible destruction and make recovery impractical.', icon: ShieldCheck },
      { title: 'IT Asset Sanitisation', shortLabel: 'Sanitize', description: 'Prepare laptops, desktops, servers and storage equipment for secure reuse, refurbishment, resale or recycling.', icon: Cpu },
      { title: 'Certificates & Reporting', shortLabel: 'Reporting', description: 'Maintain processed-device records, destruction methods, serial numbers and applicable completion certificates for organizational audits.', icon: FileCheck2 },
      { title: 'Secure Collection & Handling', shortLabel: 'Collection', description: 'Support controlled collection, inventory tracking, transportation and secure handling throughout the data destruction process.', icon: PackageCheck },
    ],
  },
  'itad-asset-buyback': {
    eyebrow: 'OUR ITAD & ASSET BUYBACK SERVICES',
    heading: 'Maximize asset value. Minimize environmental impact.',
    summary: 'Explore a secure, transparent and value-driven route from assessment and collection to responsible final disposition.',
    steps: [
      { title: 'IT Asset Assessment & Valuation', shortLabel: 'Assess', description: 'Inspect and evaluate equipment condition, specifications, age, functionality and resale potential to determine an estimated market value.', icon: Search },
      { title: 'Asset Collection & Logistics', shortLabel: 'Collect', description: 'Coordinate collection, transportation, inventory verification and handling from offices, warehouses and data centres.', icon: PackageCheck },
      { title: 'Secure Data Destruction', shortLabel: 'Sanitize', description: 'Apply appropriate data sanitization or physical destruction processes to help protect confidential business information.', icon: ShieldCheck },
      { title: 'Refurbishment & Remarketing', shortLabel: 'Refurbish', description: 'Test, repair, refurbish and prepare functional or recoverable equipment for potential resale or reuse.', icon: Cpu },
      { title: 'Responsible Recycling', shortLabel: 'Recycle', description: 'Direct equipment that cannot be economically reused to appropriate authorized channels for material recovery and sound processing.', icon: Recycle },
      { title: 'Buyback & Reporting', shortLabel: 'Report', description: 'Coordinate transparent valuation and buyback while providing relevant asset-disposition documentation for organizational records.', icon: FileCheck2 },
    ],
  },
  'epr-documentation': {
    eyebrow: 'OUR EPR DOCUMENTATION SERVICES',
    heading: 'Simplifying EPR compliance. Strengthening environmental responsibility.',
    summary: 'Select a service to explore registration, documentation, certificate management, reporting and audit support.',
    steps: [
      { title: 'EPR Registration Support', shortLabel: 'Registration', description: 'Assistance with preparing information and documentation required for applicable EPR registration through the relevant regulatory portal.', icon: ClipboardCheck },
      { title: 'Compliance Documentation', shortLabel: 'Documentation', description: 'Preparation and organization of relevant records, supporting documents, and compliance information required under applicable EPR regulations.', icon: Database },
      { title: 'Recycling Certificate Management', shortLabel: 'Certificates', description: 'Support in maintaining and organizing recycling certificates and supporting records issued by eligible registered recyclers.', icon: FileCheck2 },
      { title: 'Annual Return & Reporting Support', shortLabel: 'Reporting', description: 'Assistance with compiling relevant recycling data, EPR obligation information, and records for applicable periodic and annual reporting.', icon: PackageCheck },
      { title: 'Record Keeping & Audit Support', shortLabel: 'Audit Support', description: 'Organized documentation of collection, recycling, and material recovery activities to support compliance verification and audits.', icon: ShieldCheck },
    ],
  },
};

const serviceFlowNames: Record<string, string> = {
  'e-waste-recycling': 'E-Waste Recycling',
  'data-destruction': 'Secure Data Destruction & IT Asset Sanitisation',
  'itad-asset-buyback': 'IT Asset Disposition & Buyback Solutions',
  'epr-documentation': 'Extended Producer Responsibility (EPR) Documentation',
};

const ewasteTypes: Array<{ title: string; description: string; icon: LucideIcon }> = [
  { title: 'Desktop Computers & CPUs', description: 'Outdated desktops, processors, cabinets and internal components.', icon: Cpu },
  { title: 'Laptops & Notebooks', description: 'End-of-life laptops, accessories and electronic parts.', icon: Laptop },
  { title: 'IT & Networking Equipment', description: 'Servers, routers, switches, storage devices and network hardware.', icon: Server },
  { title: 'Circuit Boards & Components', description: 'PCBs, motherboards, chips, cables and electronic assemblies.', icon: CircuitBoard },
  { title: 'Office Electronics', description: 'Monitors, printers, scanners and office electronic equipment.', icon: Printer },
  { title: 'Electronic Batteries', description: 'Eligible batteries and battery-containing equipment through appropriate channels.', icon: Battery },
];

function DataDestructionPageContent() {
  const methods = [
    { title: 'HDD Sanitisation', description: 'Magnetic drives can be sanitised using approved overwriting or physically destroyed when required.', note: 'Reusable after successful sanitisation', icon: HardDrive },
    { title: 'SSD Sanitisation', description: 'Technology-specific sanitisation such as supported cryptographic erase or device sanitisation commands may apply. Physical destruction is used when reuse is not required.', note: 'Degaussing is not suitable for SSDs', icon: Database },
    { title: 'USB Drives & Memory Cards', description: 'Portable storage devices are sanitised or physically destroyed according to condition and security requirements.', note: 'Method depends on storage technology', icon: CircuitBoard },
    { title: 'Enterprise Storage Systems', description: 'Servers, storage arrays and data-centre equipment receive systematic media identification and sanitisation.', note: 'Serial-level asset reconciliation', icon: Server },
  ];
  const workflow = [
    ['Asset Identification & Inventory', 'Record storage devices, serial numbers and asset details.'],
    ['Security Assessment', 'Evaluate storage technology, data sensitivity and sanitisation requirements.'],
    ['Method Selection', 'Select wiping, cryptographic erase, degaussing or physical destruction.'],
    ['Secure Data Processing', 'Execute the method using suitable equipment and procedures.'],
    ['Verification & Validation', 'Verify outcomes where supported and record any exceptions.'],
    ['Certification & Reporting', 'Provide completion records, device-level reports and destruction documentation.'],
  ];
  const industries = [
    ['Banking & Finance', 'Protect confidential financial and customer information.'],
    ['Healthcare', 'Safeguard sensitive patient and medical records.'],
    ['Corporate Enterprises', 'Secure business records and internal information.'],
    ['Data Centres', 'Process enterprise storage and decommissioned servers.'],
    ['Educational Institutions', 'Protect student, staff and institutional information.'],
    ['Government Organizations', 'Support secure disposal of sensitive IT assets.'],
  ];
  const reasons = [
    ['Enhanced Data Security', 'Reduce the risk of confidential information being accessed after asset disposal.'],
    ['Documented Processes', 'Maintain transparent sanitisation, destruction and processing records.'],
    ['Asset-Level Traceability', 'Track individual devices through inventory and processing reports.'],
    ['Sustainable IT Lifecycle', 'Support reuse and recycling after appropriate sanitisation.'],
    ['Secure Asset Handling', 'Control collection, transportation, storage and processing.'],
  ];

  return (
    <>
      <section className="data-overview">
        <div>
          <p className="eyebrow"><span /> WHAT IS SECURE DATA DESTRUCTION?</p>
          <h2>Deleting is not the same as eliminating data.</h2>
          <p>Secure data destruction permanently eliminates confidential information stored on electronic devices so it cannot be accessed or recovered by unauthorized individuals.</p>
          <p>IT asset sanitisation ensures storage devices are securely processed before reuse, resale, recycling or disposal.</p>
        </div>
        <div className="data-shield-graphic" aria-label="Data protection through secure sanitization">
          <div className="data-shield"><ShieldCheck size={64} /></div>
          <span className="data-shield-line line-hdd"><HardDrive size={20} /> HDD</span>
          <span className="data-shield-line line-ssd"><Database size={20} /> SSD</span>
          <span className="data-shield-line line-server"><Server size={20} /> SERVER</span>
        </div>
      </section>

      <section className="data-methods">
        <div className="data-section-heading">
          <p className="eyebrow"><span /> OUR DATA DESTRUCTION METHODS</p>
          <h2>The method follows the storage technology.</h2>
          <p>Process selection considers device type, condition, security requirements and intended end use.</p>
        </div>
        <div className="data-method-grid">{methods.map(({ title, description, note, icon: Icon }, index) => (
          <article key={title}><span>0{index + 1}</span><Icon size={29} /><h3>{title}</h3><p>{description}</p><small>{note}</small></article>
        ))}</div>
        <div className="data-method-warning"><ShieldCheck size={21} /><p><strong>Important:</strong> Degaussing is not suitable for SSDs or flash storage. Crushing alone may not render all data unrecoverable; agree an appropriate method and verification for each device.</p></div>
      </section>

      <section className="data-workflow">
        <div className="data-section-heading">
          <p className="eyebrow light"><span /> OUR SECURE DATA DESTRUCTION WORKFLOW</p>
          <h2>Every device is identified, processed and recorded.</h2>
        </div>
        <ol>{workflow.map(([title, description], index) => <li key={title}><span>0{index + 1}</span><h3>{title}</h3><p>{description}</p></li>)}</ol>
      </section>

      <section className="data-industries">
        <div className="data-section-heading">
          <p className="eyebrow"><span /> INDUSTRIES WE SERVE</p>
          <h2>Sensitive information, protected across sectors.</h2>
        </div>
        <div className="data-industry-grid">{industries.map(([title, description]) => (
          <article key={title}><ShieldCheck size={19} /><div><h3>{title}</h3><p>{description}</p></div></article>
        ))}</div>
      </section>

      <section className="data-reasons">
        <div>
          <p className="eyebrow light"><span /> WHY CHOOSE OUR SERVICES?</p>
          <h2>Accountability continues after the last login.</h2>
        </div>
        <div className="data-reason-grid">{reasons.map(([title, description]) => (
          <article key={title}><Check size={17} /><div><h3>{title}</h3><p>{description}</p></div></article>
        ))}</div>
      </section>

      <section className="data-standards">
        <div className="data-section-heading">
          <p className="eyebrow"><span /> STANDARDS & BEST PRACTICES</p>
          <h2>Methods aligned to the applicable requirement.</h2>
          <p>References do not automatically imply certification or accreditation. The selected standard, method, evidence and scope must be confirmed for each project.</p>
        </div>
        <div className="data-standard-grid">
          <article><FileCheck2 size={24} /><strong>NIST SP 800-88 Rev. 2</strong><span>Guidelines for Media Sanitization</span></article>
          <article><ShieldCheck size={24} /><strong>ISO/IEC 27001</strong><span>Information Security Management Systems</span></article>
          <article><HardDrive size={24} /><strong>ISO/IEC 27040</strong><span>Storage Security Guidance</span></article>
          <article><ClipboardCheck size={24} /><strong>Secure Chain of Custody</strong><span>Documented tracking and device control</span></article>
        </div>
      </section>

      <section className="data-goodbye">
        <div>
          <p className="eyebrow light"><span /> GIVE YOUR DATA A SECURE GOODBYE</p>
          <h2>Protect information through its final disposition.</h2>
          <p>Equipment may have reached the end of its useful life, but data-security responsibilities continue. Choose a controlled lifecycle with traceable sanitization and responsible downstream processing.</p>
        </div>
        <div className="data-goodbye-graphic" aria-label="Secure IT lifecycle">
          <div><Database size={25} /><span>IDENTIFY</span></div>
          <ArrowRight size={18} />
          <div><ShieldCheck size={25} /><span>SANITIZE</span></div>
          <ArrowRight size={18} />
          <div><FileCheck2 size={25} /><span>VERIFY</span></div>
        </div>
      </section>
    </>
  );
}

function EwastePageContent() {
  const benefits = [
    ['Resource Conservation', 'Recovers valuable metals and materials, reducing dependence on virgin raw materials.'],
    ['Reduced Landfill Waste', 'Diverts electronic waste away from inappropriate disposal.'],
    ['Lower Environmental Impact', 'Supports responsible handling of hazardous electronic components.'],
    ['Carbon Footprint Reduction', 'Material recovery can reduce emissions linked to extracting and processing virgin resources.'],
    ['Circular Economy', 'Gives recovered materials an opportunity to return to the manufacturing cycle.'],
  ];
  return (
    <>
      <section className="ewaste-materials">
        <div className="ewaste-section-heading">
          <p className="eyebrow"><span /> TYPES OF E-WASTE WE RECYCLE</p>
          <h2>Electronics handled with clarity and care.</h2>
          <p>Acceptance is confirmed for each project based on equipment type, condition, quantity and applicable handling requirements.</p>
        </div>
        <div className="ewaste-material-grid">{ewasteTypes.map(({ title, description, icon: Icon }, index) => (
          <article key={title}>
            <span className="ewaste-material-number">0{index + 1}</span>
            <span className="ewaste-material-icon"><Icon size={28} /></span>
            <h3>{title}</h3>
            <p>{description}</p>
          </article>
        ))}</div>
      </section>

      <section className="ewaste-impact">
        <div className="ewaste-impact-copy">
          <p className="eyebrow light"><span /> ENVIRONMENTAL IMPACT</p>
          <h2>From discarded equipment to useful resources.</h2>
          <p>Recycling electronic waste is more than waste management. It conserves natural resources, reduces pollution and helps build a circular economy.</p>
          <div className="ewaste-impact-symbol" aria-hidden="true"><Recycle size={58} /><span>RECOVER</span><span>REUSE</span><span>RECYCLE</span></div>
        </div>
        <div className="ewaste-benefits">{benefits.map(([title, description], index) => (
          <article key={title}><span>0{index + 1}</span><div><h3>{title}</h3><p>{description}</p></div></article>
        ))}</div>
      </section>

      <section className="ewaste-consultation">
        <div className="ewaste-future-copy">
          <p className="eyebrow light"><span /> BUILDING A GREENER TOMORROW</p>
          <h2>Turn electronic waste into valuable resources.</h2>
          <p>Every responsibly recycled device helps conserve resources, reduce inappropriate disposal and keep useful materials moving through the economy.</p>
        </div>
        <div className="ewaste-resource-cycle" aria-label="Electronic resource recovery cycle">
          <div className="resource-cycle-center"><Recycle size={42} /><strong>VALUE<br />RECOVERED</strong></div>
          <div className="resource-cycle-item cycle-collect"><PackageCheck size={23} /><span>COLLECT</span></div>
          <div className="resource-cycle-item cycle-recover"><Factory size={23} /><span>RECOVER</span></div>
          <div className="resource-cycle-item cycle-return"><Sprout size={23} /><span>RETURN</span></div>
        </div>
      </section>
    </>
  );
}

function EprPageContent() {
  const documents = [
    ['EPR Registration Details', 'Records of applicable regulatory registration'],
    ['Business Registration Documents', 'Company identity and legal information'],
    ['Product & E-Waste Details', 'Product categories and applicable quantities'],
    ['EPR Target Records', 'Tracking of applicable recycling obligations'],
    ['Recycling Certificates', 'Evidence of eligible recycling activities'],
    ['Collection Records', 'Documentation of collected electronic waste'],
    ['Annual Returns', 'Periodic compliance reporting'],
    ['Supporting Invoices & Agreements', 'Transaction and recycler documentation'],
  ];
  const audiences = [
    ['Producers', 'Businesses manufacturing electrical and electronic equipment.', Factory],
    ['Importers', 'Businesses importing covered electronic products into India.', PackageCheck],
    ['Brand Owners', 'Businesses marketing covered products under their own brand.', Cpu],
    ['Registered Recyclers', 'Businesses involved in authorized e-waste recycling and related documentation.', Recycle],
  ] as const;
  const workflow = [
    ['Requirement Assessment', 'Understand your business category, products, and applicable EPR obligations.'],
    ['Document Collection', 'Gather business information, product details, and supporting records.'],
    ['Documentation Preparation', 'Organize information and prepare relevant compliance documentation.'],
    ['Compliance Tracking', 'Track applicable targets, recycling records, and supporting certificates.'],
    ['Reporting & Record Maintenance', 'Support reporting requirements and maintain organized records.'],
  ];
  const benefits = [
    ['Regulatory Compliance', 'Supports adherence to applicable environmental regulations.'],
    ['Transparency', 'Maintains clear records of recycling and waste-management activities.'],
    ['Improved Accountability', 'Helps businesses monitor their EPR responsibilities.'],
    ['Audit Readiness', 'Keeps supporting documents organized and accessible.'],
    ['Sustainability Reporting', 'Demonstrates environmental responsibility through documented recycling activities.'],
  ];

  return (
    <>
      <section className="epr-framework">
        <div>
          <p className="eyebrow"><span /> WHAT IS EPR?</p>
          <h2>Accountability beyond the point of sale.</h2>
          <p>Extended Producer Responsibility is a policy approach under which producers are responsible for ensuring the environmentally sound management of products and packaging after their useful life.</p>
        </div>
        <aside>
          <ShieldCheck size={34} />
          <h3>E-Waste (Management) Rules, 2022</h3>
          <p>In India, e-waste EPR requirements are primarily governed by these rules, subsequent amendments and applicable CPCB guidelines.</p>
          <small>Applicability and obligations should be confirmed for each entity and reporting period.</small>
        </aside>
      </section>

      <section className="epr-records">
        <div className="epr-section-heading">
          <p className="eyebrow"><span /> KEY EPR DOCUMENTS & RECORDS</p>
          <h2>An organized evidence trail for compliance.</h2>
          <p>Relevant documentation is organized according to business category and applicable regulatory requirements.</p>
        </div>
        <div className="epr-document-table" role="table" aria-label="Key EPR documents and purposes">
          <div className="epr-document-row epr-document-header" role="row"><strong role="columnheader">Documentation</strong><strong role="columnheader">Purpose</strong></div>
          {documents.map(([name, purpose]) => <div className="epr-document-row" role="row" key={name}><span role="cell">{name}</span><span role="cell">{purpose}</span></div>)}
        </div>
      </section>

      <section className="epr-audiences">
        <div className="epr-section-heading">
          <p className="eyebrow"><span /> WHO NEEDS EPR SUPPORT?</p>
          <h2>Documentation shaped around your role.</h2>
        </div>
        <div className="epr-audience-grid">{audiences.map(([title, description, Icon], index) => (
          <article key={title}><span>0{index + 1}</span><Icon size={28} /><h3>{title}</h3><p>{description}</p></article>
        ))}</div>
      </section>

      <section className="epr-workflow">
        <div className="epr-section-heading">
          <p className="eyebrow light"><span /> OUR EPR COMPLIANCE WORKFLOW</p>
          <h2>From requirement assessment to maintained records.</h2>
        </div>
        <ol>{workflow.map(([title, description], index) => <li key={title}><span>0{index + 1}</span><h3>{title}</h3><p>{description}</p></li>)}</ol>
      </section>

      <section className="epr-benefits">
        <div>
          <p className="eyebrow"><span /> BENEFITS OF PROPER EPR DOCUMENTATION</p>
          <h2>Compliance that is clearer, traceable and audit-ready.</h2>
        </div>
        <div className="epr-benefit-grid">{benefits.map(([title, description]) => (
          <article key={title}><Check size={17} /><div><h3>{title}</h3><p>{description}</p></div></article>
        ))}</div>
      </section>

      <section className="epr-closing">
        <div className="epr-closing-copy">
          <p className="eyebrow light"><span /> NEED ASSISTANCE WITH EPR DOCUMENTATION?</p>
          <h2>Let’s make environmental compliance simpler and more transparent.</h2>
          <p>Managing EPR requirements does not have to be complicated. Our documentation and compliance support helps organize responsibilities and maintain accurate records for a more sustainable electronics lifecycle.</p>
        </div>
        <div className="epr-compliance-graphic" aria-label="EPR documentation organized for compliance">
          <div className="epr-file-stack">
            <span className="epr-file-back" />
            <span className="epr-file-mid" />
            <div className="epr-file-front">
              <FileCheck2 size={42} />
              <strong>EPR</strong>
              <span>DOCUMENTATION</span>
              <i><Check size={13} /> RECORDS</i>
              <i><Check size={13} /> REPORTING</i>
              <i><Check size={13} /> CERTIFICATES</i>
            </div>
          </div>
          <div className="epr-compliance-seal"><ShieldCheck size={32} /><span>COMPLIANCE<br />READY</span></div>
        </div>
      </section>
    </>
  );
}

function ItadPageContent() {
  const [activeOutcome, setActiveOutcome] = useState(0);
  const assets = [
    { title: 'Desktop Computers', description: 'Office desktops, workstations and CPUs.', icon: Cpu },
    { title: 'Laptops & Notebooks', description: 'Corporate laptops, notebooks and accessories.', icon: Laptop },
    { title: 'Servers & Storage', description: 'Rack servers, storage systems and server components.', icon: Server },
    { title: 'Networking Equipment', description: 'Routers, switches, firewalls and network devices.', icon: Database },
    { title: 'Office IT Equipment', description: 'Monitors, printers, scanners and peripherals.', icon: Printer },
    { title: 'IT Components', description: 'Hard drives, SSDs, RAM, motherboards and components.', icon: CircuitBoard },
  ];
  const process = [
    ['Share Your Asset Inventory', 'Provide equipment details, quantities, specifications and available asset lists.'],
    ['Asset Inspection & Evaluation', 'Assess equipment condition, functionality, specifications and market value.'],
    ['Buyback Quotation', 'Receive an estimated quotation based on condition and resale potential.'],
    ['Collection & Secure Handling', 'Coordinate pickup, inventory verification and secure transportation.'],
    ['Data Sanitization & Processing', 'Perform appropriate sanitization, testing, refurbishment or recycling.'],
    ['Settlement & Documentation', 'Complete agreed settlement and provide transaction and disposition records.'],
  ];
  const industries = [
    'IT & Software Companies', 'Banking & Financial Institutions', 'Educational Institutions',
    'Manufacturing & Industrial Organizations', 'Healthcare Organizations', 'Corporate Offices & Enterprises',
    'Data Centres & Cloud Infrastructure Providers', 'Government & Public Sector Organizations',
  ];
  const outcomes = [
    { title: 'Recover value', label: 'VALUATION', description: 'Identify reusable, remarketable and component value from retired assets before materials are processed.', icon: IndianRupee },
    { title: 'Protect data', label: 'SECURITY', description: 'Apply the agreed sanitization or destruction controls to data-bearing equipment and storage media.', icon: ShieldCheck },
    { title: 'Extend lifecycle', label: 'REUSE', description: 'Test, refurbish and remarket suitable equipment to keep products in productive use for longer.', icon: Cpu },
    { title: 'Recover materials', label: 'RECYCLING', description: 'Route non-reusable equipment through authorized recycling channels for responsible material recovery.', icon: Recycle },
  ];
  const ActiveOutcomeIcon = outcomes[activeOutcome].icon;

  return (
    <>
      <section className="itad-overview">
        <div>
          <p className="eyebrow"><span /> WHAT IS IT ASSET DISPOSITION?</p>
          <h2>Lifecycle management that protects value and data.</h2>
          <p>IT Asset Disposition is the secure and responsible process of managing equipment that has reached the end of its operational lifecycle or is no longer required by an organization.</p>
          <p>Instead of allowing unused equipment to become electronic waste, ITAD focuses on value recovery through reuse, refurbishment, resale and responsible recycling.</p>
        </div>
        <div className="itad-value-orbit" aria-label="ITAD value recovery cycle">
          <div className="itad-value-center"><IndianRupee size={39} /><strong>ASSET<br />VALUE</strong></div>
          <span className="orbit-security"><ShieldCheck size={21} /> SECURITY</span>
          <span className="orbit-reuse"><Recycle size={21} /> REUSE</span>
          <span className="orbit-data"><Database size={21} /> DATA</span>
        </div>
      </section>

      <section className="itad-assets">
        <div className="itad-section-heading">
          <p className="eyebrow"><span /> IT ASSETS WE ACCEPT</p>
          <h2>Disposition solutions for enterprise IT equipment.</h2>
          <p>Acceptance and valuation depend on the asset type, quantity, age, condition, specifications and project requirements.</p>
        </div>
        <div className="itad-asset-grid">{assets.map(({ title, description, icon: Icon }, index) => (
          <article key={title}><span>0{index + 1}</span><Icon size={29} /><h3>{title}</h3><p>{description}</p></article>
        ))}</div>
      </section>

      <section className="itad-buyback-process">
        <div className="itad-section-heading">
          <p className="eyebrow light"><span /> OUR IT ASSET BUYBACK PROCESS</p>
          <h2>A transparent route from inventory to settlement.</h2>
        </div>
        <ol>{process.map(([title, description], index) => <li key={title}><span>0{index + 1}</span><h3>{title}</h3><p>{description}</p></li>)}</ol>
      </section>

      <section className="itad-reasons">
        <div className="itad-section-heading">
          <p className="eyebrow"><span /> WHY CHOOSE OUR ITAD SOLUTIONS?</p>
          <h2>Value recovery without losing control.</h2>
        </div>
        <div className="itad-reason-grid">
          <article><IndianRupee size={24} /><h3>Maximum Value Recovery</h3><p>Recover financial value from surplus and obsolete IT assets.</p></article>
          <article><ShieldCheck size={24} /><h3>Data Security</h3><p>Protect information through appropriate documented sanitization.</p></article>
          <article><Recycle size={24} /><h3>Responsible Recycling</h3><p>Promote reuse and responsible recycling of end-of-life electronics.</p></article>
          <article><FileCheck2 size={24} /><h3>Transparent Documentation</h3><p>Maintain records of asset collection, processing and disposition.</p></article>
        </div>
      </section>

      <section className="itad-industries">
        <div className="itad-industries-copy">
          <p className="eyebrow light"><span /> INDUSTRIES WE SERVE</p>
          <h2>ITAD support across complex operating environments.</h2>
        </div>
        <ul>{industries.map((industry) => <li key={industry}><Check size={15} /> {industry}</li>)}</ul>
      </section>

      <section className="itad-quote">
        <div className="itad-quote-copy">
          <p className="eyebrow light"><span /> FROM RETIRED ASSETS TO RECOVERED VALUE</p>
          <h2>One asset lifecycle. Four measurable outcomes.</h2>
          <p>Select an outcome to explore how ITAD balances financial recovery, data protection, reuse and responsible material recovery.</p>
        </div>
        <div className="itad-outcome-graphic">
          <div className="itad-outcome-selector" role="tablist" aria-label="ITAD value outcomes">
            {outcomes.map((outcome, index) => {
              const Icon = outcome.icon;
              return <button
                type="button"
                role="tab"
                aria-selected={activeOutcome === index}
                className={activeOutcome === index ? 'is-active' : ''}
                key={outcome.title}
                onClick={() => setActiveOutcome(index)}
              ><Icon size={21} /><span>{outcome.label}</span></button>;
            })}
          </div>
          <div className="itad-outcome-display" role="tabpanel" aria-live="polite">
            <div className="itad-outcome-icon"><ActiveOutcomeIcon size={38} /></div>
            <span>OUTCOME 0{activeOutcome + 1}</span>
            <h3>{outcomes[activeOutcome].title}</h3>
            <p>{outcomes[activeOutcome].description}</p>
          </div>
        </div>
      </section>
    </>
  );
}

const defaultServiceFlow: ServiceFlow = {
  eyebrow: 'INTERACTIVE SERVICE FLOW',
  heading: 'Explore our controlled project sequence.',
  summary: 'Select a stage to see how the project progresses from initial assessment to closeout.',
  steps: [
    { title: 'Project assessment', shortLabel: 'Assess', description: 'Review the material, site, quantity, objectives and project-specific constraints.', icon: Search },
    { title: 'Scope & controls', shortLabel: 'Plan', description: 'Define the work sequence, logistics, safety controls and required documentation.', icon: ClipboardCheck },
    { title: 'Project execution', shortLabel: 'Execute', description: 'Deliver the agreed work under coordinated supervision and controlled handovers.', icon: Factory },
    { title: 'Resource recovery', shortLabel: 'Recover', description: 'Separate and direct materials toward suitable reuse, recovery or treatment channels.', icon: Recycle },
    { title: 'Closeout', shortLabel: 'Close', description: 'Reconcile material and provide the agreed records and project outcome summary.', icon: FileCheck2 },
  ],
};

function contactStageUrl(serviceName: string, stageName: string, description: string): string {
  const params = new URLSearchParams({
    service: serviceName,
    message: `I would like to discuss ${stageName}.\n\n${description}`,
  });
  return `${pageUrl('contact')}?${params.toString()}`;
}

function readInquiryPrefill(): Partial<Pick<Inquiry, 'services' | 'message'>> {
  const params = new URLSearchParams(window.location.search);
  const service = params.get('service')?.trim();
  const message = params.get('message')?.trim();
  return {
    ...(service ? { services: [{ service, products: [{ product: '', quantity: '1' }] }] } : {}),
    ...(message ? { message } : {}),
  };
}

function InteractiveServiceFlow({ slug, serviceName }: { slug: string; serviceName: string }) {
  const [activeStep, setActiveStep] = useState(0);
  const flow = serviceFlows[slug] ?? defaultServiceFlow;
  const active = flow.steps[activeStep];
  const ActiveIcon = active.icon;

  return (
    <section className={`interactive-service-flow flow-${slug}`} id="interactive-service-flow" aria-labelledby={`service-flow-${slug}`}>
      <div className="service-flow-intro">
        <p className="eyebrow"><span /> {flow.eyebrow}</p>
        <h2 id={`service-flow-${slug}`}>{flow.heading}</h2>
        <p>{flow.summary}</p>
      </div>
      <div className="service-flow-layout">
        <div className={`service-flow-track${flow.steps.length === 6 ? ' is-six-step' : ''}`} role="tablist" aria-label={`${cleanText(pageLabels[slug] ?? 'Service')} process stages`}>
          {flow.steps.map((step, index) => {
            const Icon = step.icon;
            const isActive = index === activeStep;
            return <button
              className={`service-flow-step${isActive ? ' is-active' : ''}`}
              type="button"
              role="tab"
              aria-selected={isActive}
              aria-controls="service-flow-panel"
              id={`service-flow-tab-${index}`}
              tabIndex={isActive ? 0 : -1}
              key={step.title}
              onClick={() => setActiveStep(index)}
              onKeyDown={(event) => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const nextIndex = event.key === 'Home'
                  ? 0
                  : event.key === 'End'
                    ? flow.steps.length - 1
                    : (index + (event.key === 'ArrowRight' ? 1 : -1) + flow.steps.length) % flow.steps.length;
                setActiveStep(nextIndex);
                requestAnimationFrame(() => document.getElementById(`service-flow-tab-${nextIndex}`)?.focus());
              }}
            >
              <span className="service-flow-node"><Icon size={22} /></span>
              <span className="service-flow-number">0{index + 1}</span>
              <strong>{step.shortLabel}</strong>
            </button>;
          })}
        </div>
        <div className="service-flow-panel" role="tabpanel" id="service-flow-panel" aria-labelledby={`service-flow-tab-${activeStep}`} aria-live="polite">
          <div className="service-flow-panel-icon"><ActiveIcon size={34} /></div>
          <div>
            <span>STAGE 0{activeStep + 1} OF 0{flow.steps.length}</span>
            <h3>{active.title}</h3>
            <p>{active.description}</p>
          </div>
          <a href={contactStageUrl(serviceName, active.title, active.description)}>Discuss this stage <ArrowUpRight size={15} /></a>
        </div>
      </div>
    </section>
  );
}

function ServiceHeroArtwork({
  services,
  activeSlug,
  onSelect,
}: {
  services: ServiceDirectoryItem[];
  activeSlug: string;
  onSelect: (slug: string) => void;
}) {
  const activeService = services.find((service) => service.slug === activeSlug) ?? services[0];
  if (!activeService) return null;

  const ActiveIcon = activeService.icon;
  const nodePositions = ['one', 'two', 'three', 'four'];

  return (
    <div className="service-hero-art" role="group" aria-label="Choose a service">
      <span className="service-hero-orbit service-hero-orbit-outer" aria-hidden="true" />
      <span className="service-hero-orbit service-hero-orbit-inner" aria-hidden="true" />
      <div className="service-hero-core" aria-hidden="true"><ActiveIcon size={65} strokeWidth={1.2} /></div>
      {services.slice(0, nodePositions.length).map((service, index) => {
        const Icon = service.icon;
        const isActive = service.slug === activeService.slug;
        return <button
          className={`service-hero-node service-hero-node-${nodePositions[index]}${isActive ? ' is-active' : ''}`}
          type="button"
          key={service.slug}
          aria-label={`Show ${service.title}`}
          aria-pressed={isActive}
          onClick={() => onSelect(service.slug)}
        ><Icon size={21} /></button>;
      })}
      <span className="service-hero-caption" aria-live="polite">
        {serviceArtLabels[activeService.slug] ?? activeService.title}
      </span>
    </div>
  );
}

function ProjectCard({ project }: { project: WordPressItem }) {
  const media = project._embedded?.['wp:featuredmedia']?.[0];
  const projectPhotos = Array.isArray(project.meta?.vanregen_project_gallery) ? project.meta.vanregen_project_gallery : [];
  const coverImage = media?.source_url ?? projectPhotos[0];
  const summary = cleanText(project.excerpt.rendered || project.content.rendered).slice(0, 170);

  return (
    <a className="project-card" href={project.link}>
      {coverImage && <img className="project-card-image" src={coverImage} alt={media?.alt_text || cleanText(project.title.rendered)} />}
      <div className="project-card-copy">
        <h3>{cleanText(project.title.rendered)}</h3>
        {summary && <p>{summary}</p>}
        <span className="project-card-link">View project <ArrowUpRight size={16} /></span>
      </div>
    </a>
  );
}

function cleanText(html: string): string {
  const element = document.createElement('div');
  element.innerHTML = html;
  return element.textContent?.trim() ?? '';
}

function normalizeCopy(text: string): string {
  return text.replace(/\s+/g, ' ').trim().toLocaleLowerCase();
}

function removeRepeatedParagraphs(html: string, repeatedCopy: string[]): string {
  if (!html || repeatedCopy.length === 0) return html;
  const repeated = new Set(repeatedCopy.map(normalizeCopy).filter(Boolean));
  const content = document.createElement('div');
  content.innerHTML = html;
  content.querySelectorAll('p').forEach((paragraph) => {
    if (repeated.has(normalizeCopy(paragraph.textContent ?? ''))) paragraph.remove();
  });
  return content.innerHTML;
}

function pageUrl(slug: string): string {
  return api?.pageUrls[slug] ?? (slug === 'home' ? '/' : `/${slug}/`);
}

function siteCopy(key: string, fallback: string): string {
  return api?.siteCopy?.[key]?.trim() || fallback;
}

function readMetaText(meta: Record<string, string | string[]> | undefined, key: string): string {
  const value = meta?.[key];
  return typeof value === 'string' ? value.trim() : '';
}

function readMetaNumber(meta: Record<string, string | string[]> | undefined, key: string): number | undefined {
  const value = readMetaText(meta, key);
  if (value === '') return undefined;
  const number = Number(value);
  return Number.isFinite(number) ? number : undefined;
}

function currentSlug(): string {
  if (api?.pageSlug) return api.pageSlug;
  const slug = window.location.pathname.split('/').filter(Boolean).at(-1);
  return slug ?? 'home';
}

async function getJson<T>(path: string): Promise<T> {
  if (!api?.restUrl) throw new Error('WordPress REST API is not configured.');
  const response = await fetch(`${api.restUrl}${path}`, { cache: 'no-store', headers: { Accept: 'application/json' } });
  if (!response.ok) throw new Error(`WordPress returned ${response.status}.`);
  return response.json() as Promise<T>;
}

function App() {
  const slug = currentSlug();
  const isHome = api?.isHome ?? (slug === 'home' || slug === '');
  const isServicePost = api?.postType === 'services';
  const isProjectPost = api?.postType === 'projects';
  const isPost = api?.postType === 'post';
  const isServiceDetail = isServicePost || Boolean(servicePageSlugs[slug]);
  const [menuOpen, setMenuOpen] = useState(false);
  const [servicesMenuOpen, setServicesMenuOpen] = useState(false);
  const [prefersReducedMotion, setPrefersReducedMotion] = useState(false);
  const [page, setPage] = useState<WordPressPage | null>(null);
  const [servicePage, setServicePage] = useState<WordPressItem | null>(null);
  const [project, setProject] = useState<WordPressItem | null>(null);
  const [blogPost, setBlogPost] = useState<WordPressItem | null>(null);
  const [services, setServices] = useState<WordPressItem[]>([]);
  const [activeServiceSlug, setActiveServiceSlug] = useState('');
  const [projects, setProjects] = useState<WordPressItem[]>([]);
  const [activeGallery, setActiveGallery] = useState<{ title: string; photos: string[] } | null>(null);
  const [contentError, setContentError] = useState('');
  const [wasteKg, setWasteKg] = useState(15000);
  const [wasteModel, setWasteModel] = useState<'mixed' | 'it' | 'mobile'>('mixed');
  const [recoveryScenario, setRecoveryScenario] = useState<'conservative' | 'base' | 'optimistic'>('base');
  const [formState, setFormState] = useState<'idle' | 'sending' | 'sent' | 'error'>('idle');
  const [formError, setFormError] = useState('');
  const [formSuccessMessage, setFormSuccessMessage] = useState('Your inquiry was received.');
  const [inquiry, setInquiry] = useState<Inquiry>(() => ({
    name: '',
    company: '',
    email: '',
    phone: '',
    services: [{ service: '', products: [{ product: '', quantity: '1' }] }],
    message: '',
    consent: false,
    website: '',
    ...readInquiryPrefill(),
  }));

  useEffect(() => {
    if (!['contact', 'contact-quote'].includes(slug) || !new URLSearchParams(window.location.search).has('message')) return;
    const textarea = document.getElementById('inquiry-message');
    const focusTimer = window.setTimeout(() => {
      textarea?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' });
      textarea?.focus();
    }, 100);
    return () => window.clearTimeout(focusTimer);
  }, [prefersReducedMotion, slug]);

  useEffect(() => {
    const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
    const updateMotionPreference = () => setPrefersReducedMotion(motionPreference.matches);
    updateMotionPreference();
    motionPreference.addEventListener('change', updateMotionPreference);
    return () => motionPreference.removeEventListener('change', updateMotionPreference);
  }, []);

  useEffect(() => {
    if (!activeGallery) return;
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setActiveGallery(null);
    };
    document.body.classList.add('gallery-modal-open');
    window.addEventListener('keydown', closeOnEscape);
    return () => {
      document.body.classList.remove('gallery-modal-open');
      window.removeEventListener('keydown', closeOnEscape);
    };
  }, [activeGallery]);

  useEffect(() => {
    if (!api?.restUrl) return;
    const requests: Promise<void>[] = [];
    if (!isServicePost && !isProjectPost && !isPost) {
      requests.push(
        getJson<WordPressPage[]>(`wp/v2/pages?slug=${encodeURIComponent(isHome ? 'home' : slug)}&status=publish&_fields=slug,title,content,excerpt,link,meta`)
          .then((pages) => setPage(pages[0] ?? null))
          .catch(() => setContentError('Page content could not be loaded from WordPress.')),
      );
    }
    if (isPost) {
      requests.push(
        getJson<WordPressItem[]>(`wp/v2/posts?slug=${encodeURIComponent(slug)}&_embed`)
          .then((posts) => setBlogPost(posts[0] ?? null))
          .catch(() => setBlogPost(null)),
      );
    }
    const serviceSlug = servicePageSlugs[slug];
    if (isServicePost || serviceSlug) {
      requests.push(
        getJson<WordPressItem[]>(`wp/v2/services?slug=${encodeURIComponent(serviceSlug ?? slug)}&_embed`)
          .then((items) => setServicePage(items[0] ?? null))
          .catch(() => setServicePage(null)),
      );
    }
    if (isProjectPost || (!isHome && !knownPageSlugs.has(slug) && !isServicePost && !isPost)) {
      requests.push(
        getJson<WordPressItem[]>(`wp/v2/projects?slug=${encodeURIComponent(slug)}&_embed`)
          .then((items) => setProject(items[0] ?? null))
          .catch(() => setProject(null)),
      );
    }
    if (isHome || isServicePost || Boolean(servicePageSlugs[slug]) || ['services', 'contact-quote'].includes(slug)) {
      requests.push(
        getJson<WordPressItem[]>('wp/v2/services?per_page=100&_embed&status=publish')
          .then(setServices)
          .catch(() => setServices([])),
      );
    }
    if (isHome || slug === 'projects') {
      requests.push(
        getJson<WordPressItem[]>('wp/v2/projects?per_page=100&_embed&status=publish')
          .then(setProjects)
          .catch(() => setProjects([])),
      );
    }
    void Promise.all(requests);
  }, [isHome, isPost, isProjectPost, isServicePost, slug]);

  function updateInquiry<K extends keyof Inquiry>(key: K, value: Inquiry[K]) {
    setInquiry((current) => ({ ...current, [key]: value }));
  }

  function selectInquiryService(serviceIndex: number, service: string) {
    setInquiry((current) => ({
      ...current,
      services: current.services.map((selection, index) => (
        index === serviceIndex ? { service, products: [{ product: '', quantity: '1' }] } : selection
      )),
    }));
  }

  function addInquiryService() {
    setInquiry((current) => ({
      ...current,
      services: [...current.services, { service: '', products: [{ product: '', quantity: '1' }] }],
    }));
  }

  function removeInquiryService(serviceIndex: number) {
    setInquiry((current) => ({
      ...current,
      services: current.services.filter((_, index) => index !== serviceIndex),
    }));
  }

  function updateInquiryProduct(serviceIndex: number, productIndex: number, key: keyof InquiryProduct, value: string) {
    setInquiry((current) => ({
      ...current,
      services: current.services.map((selection, index) => index === serviceIndex
        ? {
            ...selection,
            products: selection.products.map((product, index) => (
              index === productIndex ? { ...product, [key]: value } : product
            )),
          }
        : selection),
    }));
  }

  function addInquiryProduct(serviceIndex: number) {
    setInquiry((current) => ({
      ...current,
      services: current.services.map((selection, index) => index === serviceIndex
        ? { ...selection, products: [...selection.products, { product: '', quantity: '1' }] }
        : selection),
    }));
  }

  function removeInquiryProduct(serviceIndex: number, productIndex: number) {
    setInquiry((current) => ({
      ...current,
      services: current.services.map((selection, index) => index === serviceIndex
        ? { ...selection, products: selection.products.filter((_, index) => index !== productIndex) }
        : selection),
    }));
  }

  async function submitInquiry(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFormState('sending');
    setFormError('');
    try {
      const contextService = inquiry.services.find((selection) => selection.service)?.service;
      const requestPayload = slug === 'contact-quote'
        ? { ...inquiry, request_type: 'quote' }
        : {
            name: inquiry.name,
            company: inquiry.company,
            email: inquiry.email,
            phone: inquiry.phone,
            message: inquiry.message,
            consent: inquiry.consent,
            website: inquiry.website,
            request_type: 'contact',
            ...(contextService ? { context_service: contextService } : {}),
          };
      const response = await fetch(`${api?.restUrl ?? '/wp-json/'}vanregen/v1/inquiries`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(api?.restNonce ? { 'X-WP-Nonce': api.restNonce } : {}),
        },
        body: JSON.stringify(requestPayload),
      });
      const result = await response.json() as { message?: string };
      if (!response.ok) throw new Error(result.message ?? 'Your request could not be sent. Please try again.');
      setFormSuccessMessage(result.message ?? 'Your inquiry was received.');
      setFormState('sent');
      setInquiry({ name: '', company: '', email: '', phone: '', services: [{ service: '', products: [{ product: '', quantity: '1' }] }], message: '', consent: false, website: '' });
    } catch (error) {
      setFormError(error instanceof Error ? error.message : 'Something went wrong. Please try again.');
      setFormState('error');
    }
  }

  const title = slug === 'contact-quote'
    ? cleanText(page?.title.rendered ?? '') || 'Request a Quote'
    : servicePage ? cleanText(servicePage.title.rendered) : page ? cleanText(page.title.rendered) : project ? cleanText(project.title.rendered) : blogPost ? cleanText(blogPost.title.rendered) : pageLabels[slug] ?? 'Van Regen';
  const content = servicePage?.content.rendered ?? page?.content.rendered ?? project?.content.rendered ?? blogPost?.content.rendered ?? '';
  const detailMedia = servicePage?._embedded?.['wp:featuredmedia']?.[0] ?? project?._embedded?.['wp:featuredmedia']?.[0] ?? blogPost?._embedded?.['wp:featuredmedia']?.[0];
  const pageCopy = (key: string, fallback: string) => readMetaText(page?.meta, `vanregen_${key}`) || fallback;
  const sustainabilityProcessedKg = readMetaNumber(page?.meta, 'vanregen_sustainability_processed_kg');
  const modelPrefix = wasteModel === 'mixed' ? '' : `${wasteModel}_`;
  const mixedMetalRecoveryRate = readMetaNumber(page?.meta, 'vanregen_sustainability_metal_recovery_percent');
  const mixedCo2eAvoidedPerKg = readMetaNumber(page?.meta, 'vanregen_sustainability_co2e_avoided_per_kg');
  const selectedMetalRecoveryRate = readMetaNumber(page?.meta, `vanregen_sustainability_${modelPrefix}metal_recovery_percent`);
  const selectedCo2eAvoidedPerKg = readMetaNumber(page?.meta, `vanregen_sustainability_${modelPrefix}co2e_avoided_per_kg`);
  const metalRecoveryRate = selectedMetalRecoveryRate ?? mixedMetalRecoveryRate;
  const co2eAvoidedPerKg = selectedCo2eAvoidedPerKg ?? mixedCo2eAvoidedPerKg;
  const usesMixedFallback = wasteModel !== 'mixed' && (selectedMetalRecoveryRate === undefined || selectedCo2eAvoidedPerKg === undefined);
  const scenarioMultiplier = readMetaNumber(page?.meta, `vanregen_sustainability_scenario_${recoveryScenario}_multiplier`) ?? 1;
  const energyRecoveredPerKg = readMetaNumber(page?.meta, 'vanregen_sustainability_energy_recovered_kwh_per_kg');
  const landfillDivertedPercent = readMetaNumber(page?.meta, 'vanregen_sustainability_landfill_diverted_percent');
  const waterSavedPerKg = readMetaNumber(page?.meta, 'vanregen_sustainability_water_saved_l_per_kg');
  const estimatedMetalsKg = metalRecoveryRate === undefined ? undefined : wasteKg * metalRecoveryRate / 100 * scenarioMultiplier;
  const estimatedCo2eKg = co2eAvoidedPerKg === undefined ? undefined : wasteKg * co2eAvoidedPerKg * scenarioMultiplier;
  const estimatedEnergyKwh = energyRecoveredPerKg === undefined ? undefined : wasteKg * energyRecoveredPerKg * scenarioMultiplier;
  const estimatedLandfillKg = landfillDivertedPercent === undefined ? undefined : wasteKg * landfillDivertedPercent / 100 * scenarioMultiplier;
  const estimatedWaterLitres = waterSavedPerKg === undefined ? undefined : wasteKg * waterSavedPerKg * scenarioMultiplier;
  const formatModelEstimate = (value: number | undefined, unit: string) => value === undefined ? 'Not configured' : `${Math.round(value).toLocaleString()} ${unit}`;
  const sustainabilityModelNote = `${usesMixedFallback ? 'Category-specific rates are incomplete; mixed-electronics rates are used where blank. ' : ''}Factors: metals ${metalRecoveryRate ?? 'not configured'}%; CO2e ${co2eAvoidedPerKg ?? 'not configured'} kg/kg; energy ${energyRecoveredPerKg ?? 'not configured'} kWh/kg; landfill ${landfillDivertedPercent ?? 'not configured'}%; water ${waterSavedPerKg ?? 'not configured'} L/kg; scenario ${scenarioMultiplier}x.`;
  const heroMediaMeta = isServiceDetail ? servicePage?.meta : isProjectPost ? project?.meta : isPost ? blogPost?.meta : page?.meta;
  const heroVideoUrl = readMetaText(heroMediaMeta, 'vanregen_page_hero_video_url');
  const heroImageUrl = readMetaText(heroMediaMeta, 'vanregen_page_hero_image_url');
  const heroVideoCredit = readMetaText(heroMediaMeta, 'vanregen_page_hero_video_credit');
  const contactEmails = pageCopy('contact_emails', 'info@vanregen.in\nranjit@vanregen.in').split(/\r?\n/).map((email) => email.trim()).filter(Boolean);
  const contactPhones = pageCopy('contact_phones', '+91 96655 55591\n+91 94231 37475').split(/\r?\n/).map((phone) => phone.trim()).filter(Boolean);
  const contactFormHeading = pageCopy(
    'contact_heading',
    slug === 'contact-quote' ? 'Tell us which services and products you need.' : 'Tell us what you are working through.',
  );
  const contactFormDescription = pageCopy(
    'contact_description',
    slug === 'contact-quote'
      ? 'Choose the services, products and quantities for your quote, then share your contact details.'
      : 'Share a few details and the team can follow up about your request.',
  );
  const summaryCopy = readMetaText(page?.meta, 'vanregen_page_summary') || cleanText(servicePage?.excerpt.rendered ?? page?.excerpt.rendered ?? project?.excerpt.rendered ?? blogPost?.excerpt.rendered ?? '');
  const normalizedBody = normalizeCopy(cleanText(content));
  const pageSummary = slug === 'contact-quote'
    ? summaryCopy || 'Choose one or more services, products, and quantities, then share your contact details.'
    : summaryCopy && normalizedBody.includes(normalizeCopy(summaryCopy))
      ? ''
      : summaryCopy || 'Thoughtful resource recovery and industrial services, shaped around your materials and your operation.';
  const serviceHeroTitle = (servicePage?.slug ?? slug) === 'e-waste-recycling'
    ? 'Responsible E-Waste Recycling Solutions'
    : (servicePage?.slug ?? slug) === 'epr-documentation'
      ? 'Extended Producer Responsibility (EPR) Documentation'
      : (servicePage?.slug ?? slug) === 'itad-asset-buyback'
        ? 'IT Asset Disposition (ITAD) & Buyback Solutions'
        : (servicePage?.slug ?? slug) === 'data-destruction'
          ? 'Secure Data Destruction & IT Asset Sanitisation'
          : title;
  const serviceHeroSummary = (servicePage?.slug ?? slug) === 'e-waste-recycling'
    ? 'Give obsolete electronics a second life through secure, responsible and environmentally conscious collection, dismantling, material recovery and disposal.'
    : (servicePage?.slug ?? slug) === 'epr-documentation'
      ? 'Simplifying EPR compliance. Strengthening environmental responsibility through organized documentation, accurate records and transparent waste-management support.'
      : (servicePage?.slug ?? slug) === 'itad-asset-buyback'
        ? 'Maximize Asset Value. Minimize Environmental Impact. Secure collection, evaluation, data sanitization, refurbishment, resale and responsible recycling for retiring IT infrastructure.'
        : (servicePage?.slug ?? slug) === 'data-destruction'
          ? 'Your Data. Your Security. Our Responsibility. Structured sanitization and destruction solutions for storage devices and enterprise IT equipment.'
          : pageSummary;
  const renderedPageContent = isHome
    ? removeRepeatedParagraphs(content, [pageCopy('home_intro_body', ''), summaryCopy])
    : content;
  const legacyServicesCopy = [
    'Every engagement follows an accountable workflow from enterprise intake through documented closure.',
    'Additional services described',
    'The source profile also mentions',
  ];
  const pageBodyContent = slug === 'services' && legacyServicesCopy.some((marker) => content.includes(marker))
    ? ''
    : content;
  const serviceItems: ServiceDirectoryItem[] = services.length
    ? services.map((item) => ({
        title: cleanText(item.title.rendered),
        excerpt: cleanText(item.excerpt.rendered || item.content.rendered),
        icon: serviceIcons[item.slug] ?? Cpu,
        image: item._embedded?.['wp:featuredmedia']?.[0]?.source_url ?? '',
        slug: item.slug,
        link: item.link,
        products: Array.isArray(item.meta?.vanregen_service_products)
          ? item.meta.vanregen_service_products.filter((product): product is string => typeof product === 'string')
          : [],
      })).sort((a, b) => {
        const aIndex = serviceQuoteOrder.indexOf(a.slug);
        const bIndex = serviceQuoteOrder.indexOf(b.slug);
        return (aIndex === -1 ? serviceQuoteOrder.length : aIndex) - (bIndex === -1 ? serviceQuoteOrder.length : bIndex);
      })
    : api?.restUrl ? [] : serviceFallbacks;
  const activeDirectoryService = serviceItems.find((service) => service.slug === activeServiceSlug) ?? serviceItems[0];
  const canAddInquiryService = serviceItems.length > inquiry.services.length
    && inquiry.services.every((selection) => Boolean(selection.service));
  const serviceDetailImage = servicePage ? serviceItems.find((service) => service.slug === servicePage.slug)?.image : undefined;
  const detailImageUrl = detailMedia?.source_url ?? serviceDetailImage;
  const projectPhotos = project && Array.isArray(project.meta?.vanregen_project_gallery)
    ? project.meta.vanregen_project_gallery.filter((url): url is string => typeof url === 'string')
    : [];
  const pageGalleryPhotos = page && Array.isArray(page.meta?.vanregen_projects_gallery)
    ? page.meta.vanregen_projects_gallery.filter((url): url is string => typeof url === 'string')
    : [];
  const projectGalleryGroups = projects.map((item) => ({
    project: item,
    photos: Array.isArray(item.meta?.vanregen_project_gallery)
      ? [...new Set(item.meta.vanregen_project_gallery.filter((url): url is string => typeof url === 'string'))]
      : [],
  })).filter((group) => group.photos.length > 0);
    const hasProjectGalleryPhotos = projectGalleryGroups.length > 0 || pageGalleryPhotos.length > 0;
    const heroMediaPoster = heroImageUrl || detailMedia?.source_url || (isServiceDetail ? detailImageUrl : undefined) || (isHome || slug === 'services' ? serviceItems[0]?.image : undefined);
    const usesMediaHero = isServiceDetail || slug === 'services' || Boolean(heroImageUrl || heroVideoUrl || detailMedia?.source_url);
  const headerOverMedia = isHome || usesMediaHero;
  const navigationItems: NavigationItem[] = api?.navigation?.length
    ? api.navigation
    : [
        { id: 'services', title: 'Services', url: pageUrl('services') },
        { id: 'about', title: 'About', url: pageUrl('about') },
        { id: 'projects', title: 'Projects', url: pageUrl('projects') },
        { id: 'sustainability', title: 'Sustainability', url: pageUrl('sustainability') },
        { id: 'contact', title: 'Contact', url: pageUrl('contact') },
      ];
  const headerNavigationItems = navigationItems.filter((item) => !cleanText(item.title).toLowerCase().includes('contact'));
  return (
    <div className="site-shell">
      <div className="topline">
        <span>{siteCopy('companyName', 'VAN REGEN PRIVATE LIMITED')}</span>
        <span className="topline-right">
          <span>{siteCopy('toplineDescriptor', 'RESOURCE RECOVERY / INDUSTRIAL SERVICES')}</span>
          <a className="topline-quote" href={pageUrl('contact-quote')}>Ask a Quote <ArrowUpRight size={13} /></a>
        </span>
      </div>
      <header className={`site-header${headerOverMedia ? ' site-header-over-media' : ''}`}>
        <a className="brand" href={pageUrl('home')} aria-label="Van Regen home">
          <span className="brand-mark">{api?.logoUrl ? <img className="brand-logo" src={api.logoUrl} alt="" /> : <Recycle size={25} strokeWidth={2.2} />}</span>
          <span className="brand-name">{siteCopy('brandName', 'VAN REGEN')}<small>{siteCopy('brandTagline', 'RESPONSIBLE RESOURCE RECOVERY')}</small></span>
        </a>
        <button className="menu-toggle" aria-label={menuOpen ? 'Close menu' : 'Open menu'} aria-expanded={menuOpen} onClick={() => setMenuOpen(!menuOpen)}>
          {menuOpen ? <X size={22} /> : <Menu size={22} />}
        </button>
        <nav className={`main-nav${menuOpen ? ' is-open' : ''}`} aria-label="Main navigation">
          {headerNavigationItems.map((item) => {
            const children = item.children ?? [];
            const label = cleanText(item.title);
            if (label.toLowerCase() === 'services' && children.length > 0) {
              return <div className={`nav-services${servicesMenuOpen ? ' is-open' : ''}${isServiceDetail ? ' is-current' : ''}`} key={item.id}>
                <a href={item.url} target={item.target || undefined} aria-current={isServiceDetail ? 'page' : undefined} onClick={() => setMenuOpen(false)}>{label}</a>
                <button className="services-menu-toggle" type="button" aria-label="Toggle services menu" aria-expanded={servicesMenuOpen} onClick={() => setServicesMenuOpen(!servicesMenuOpen)}><ChevronDown size={14} /></button>
                <div className="services-dropdown">{children.map((child) => <a key={child.id} href={child.url} target={child.target || undefined} onClick={() => { setServicesMenuOpen(false); setMenuOpen(false); }}>{cleanText(child.title)} <ArrowUpRight size={13} /></a>)}</div>
              </div>;
            }
            return <a key={item.id} href={item.url} target={item.target || undefined} onClick={() => setMenuOpen(false)}>{label}</a>;
          })}
        </nav>
      </header>

      {isHome ? (
        <>
          <main>
            <section className="hero">
              <div className="hero-media" aria-hidden="true" style={heroMediaPoster ? { backgroundImage: `url("${heroMediaPoster}")` } : undefined}>
                {!prefersReducedMotion && heroVideoUrl && <video className="hero-video" autoPlay muted loop playsInline preload="metadata" poster={heroMediaPoster}>
                  <source src={heroVideoUrl} type="video/mp4" />
                </video>}
                <div className="hero-wash" />
              </div>
              <div className="hero-content">
                <p className="eyebrow light"><span /> {pageCopy('home_hero_eyebrow', 'CIRCULAR THINKING. INDUSTRIAL SCALE.')}</p>
                <h1>{pageCopy('home_hero_heading', 'VAN REGEN')}<br /><span>{pageCopy('home_hero_tagline', 'Resourceful by nature.')}</span></h1>
                <p className="hero-copy">{pageCopy('home_hero_summary', 'A thoughtful partner for material recovery, equipment retirement and industrial change.')}</p>
                <div className="hero-actions"><a className="button button-lime" href={pageUrl('services')}>{pageCopy('home_primary_link', 'Explore our services')} <ArrowRight size={17} /></a></div>
              </div>
              {heroVideoCredit && <p className="hero-video-credit">{heroVideoCredit}</p>}
              <a className="hero-side-link" href={pageUrl('sustainability')}>MATERIALS IN CIRCULATION <ArrowUpRight size={13} /></a>
            </section>

            {renderedPageContent && <article className="wordpress-content home-content" dangerouslySetInnerHTML={{ __html: renderedPageContent }} />}

            <section className="impact-band">
              <div className="impact-copy"><p className="eyebrow light"><span /> {pageCopy('home_impact_eyebrow', 'MATERIALS, MEASURED')}</p><h2>{pageCopy('home_impact_heading', 'See your material\nin a new light.').split('\n').map((line, index) => <Fragment key={line + index}>{index > 0 && <br />}{line}</Fragment>)}</h2><p>{pageCopy('home_impact_body', 'Explore an illustrative view of material volume and potential tree-planting equivalents. Actual environmental outcomes depend on verified project data.')}</p><a className="text-link light-link" href={pageUrl('sustainability')}>{pageCopy('home_impact_link', 'Our approach to impact')} <ArrowRight size={16} /></a></div>
              <div className="calculator"><div className="calc-head"><span>{pageCopy('home_calculator_label', 'VOLUME ESTIMATOR')}</span><span>{pageCopy('home_calculator_badge', 'ILLUSTRATIVE ONLY')}</span></div><label htmlFor="waste-volume">{pageCopy('home_calculator_volume_label', 'Estimated material volume')} <strong>{wasteKg.toLocaleString()} kg</strong></label><input id="waste-volume" type="range" min="1000" max="100000" step="1000" value={wasteKg} onChange={(event) => setWasteKg(Number(event.target.value))} /><div className="range-labels"><span>1,000 kg</span><span>100,000 kg</span></div><div className="calc-result"><div><Sprout size={19} /><strong>{Math.floor(wasteKg / 1000)}</strong><span>{pageCopy('home_calculator_tree_label', 'illustrative tree equivalents*')}</span></div><p>{pageCopy('home_calculator_notice', '*Based on a hypothetical ratio of one tree per 1,000 kg. No planting or carbon credit is guaranteed by this estimate.')}</p></div></div>
            </section>

          </main>
        </>
      ) : (
        <main className="inner-page">
          {slug === 'projects' ? null : usesMediaHero
            ? <section className={`hero service-hero${slug === 'services' ? ' service-directory-hero' : ''}`}>
                <div className="hero-media" aria-hidden="true" style={heroMediaPoster ? { backgroundImage: `url("${heroMediaPoster}")` } : undefined}>
                  {!prefersReducedMotion && heroVideoUrl && <video className="hero-video" autoPlay muted loop playsInline preload="metadata" poster={heroMediaPoster}>
                    <source src={heroVideoUrl} type="video/mp4" />
                  </video>}
                </div>
                {slug === 'services' && <ServiceHeroArtwork
                  services={serviceItems}
                  activeSlug={activeDirectoryService?.slug ?? ''}
                  onSelect={setActiveServiceSlug}
                />}
                <div className="hero-content">
                  <p className="eyebrow light"><span /> {isServiceDetail ? `SERVICES / ${serviceHeroTitle}` : slug === 'services' ? 'OUR SERVICES' : `${siteCopy('brandName', 'VAN REGEN')} / ${title}`}</p>
                  <h1 aria-live={slug === 'services' ? 'polite' : undefined}>{slug === 'services' ? activeDirectoryService?.title ?? serviceHeroTitle : serviceHeroTitle}</h1>
                  <p className="hero-copy">{slug === 'services' ? activeDirectoryService?.excerpt ?? serviceHeroSummary : serviceHeroSummary}</p>
                  {slug === 'services' && activeDirectoryService && <a className="button button-lime service-hero-cta" href={activeDirectoryService.link || pageUrl(activeDirectoryService.slug)}>Explore this service <ArrowRight size={17} /></a>}
                  {isServiceDetail && <a className="text-link light-link service-process-link" href="#interactive-service-flow">View our process <ArrowRight size={16} /></a>}
                  {slug === 'services' && serviceItems.length > 0 && <div className="service-hero-selector" role="group" aria-label="Select a service">
                    {serviceItems.map((service) => {
                      const Icon = service.icon;
                      const isActive = service.slug === activeDirectoryService?.slug;
                      return <button
                        className={`service-hero-select${isActive ? ' is-active' : ''}`}
                        type="button"
                        key={service.slug}
                        aria-pressed={isActive}
                        onClick={() => setActiveServiceSlug(service.slug)}
                      ><Icon size={16} /><span>{serviceShortNames[service.slug] ?? service.title}</span></button>;
                    })}
                  </div>}
                </div>
                {heroVideoCredit && <p className="hero-video-credit">{heroVideoCredit}</p>}
              </section>
            : <section className={`inner-hero${slug === 'sustainability' ? ' sustainability-hero' : ''}`}><p className="eyebrow"><span /> {siteCopy('brandName', 'VAN REGEN')} / {title}</p><h1>{title}</h1><p>{pageSummary}</p>{slug === 'sustainability' && <div className="sustainability-counter" aria-live="polite"><span className="sustainability-counter-label">E-waste collected &amp; processed</span><div><strong>{sustainabilityProcessedKg === undefined ? '—' : sustainabilityProcessedKg.toLocaleString()}</strong><span>kg</span></div><small>{sustainabilityProcessedKg === undefined ? 'Awaiting verified total' : 'Total entered from verified records'}</small></div>}</section>}
          {contentError && <p className="api-message">{contentError}</p>}
          {detailImageUrl && !isServiceDetail && !usesMediaHero && <figure className="detail-featured-image"><img src={detailImageUrl} alt={detailMedia?.alt_text || title} /></figure>}
          {isServiceDetail && <InteractiveServiceFlow
            slug={servicePage?.slug ?? slug}
            serviceName={cleanText(servicePage?.title.rendered ?? '') || serviceFlowNames[slug] || title}
          />}
          {(servicePage?.slug ?? slug) === 'e-waste-recycling' && <EwastePageContent />}
          {(servicePage?.slug ?? slug) === 'epr-documentation' && <EprPageContent />}
          {(servicePage?.slug ?? slug) === 'itad-asset-buyback' && <ItadPageContent />}
          {(servicePage?.slug ?? slug) === 'data-destruction' && <DataDestructionPageContent />}
          {pageBodyContent && !['e-waste-recycling', 'epr-documentation', 'itad-asset-buyback', 'data-destruction'].includes(servicePage?.slug ?? slug) && (slug !== 'projects' || isProjectPost) && <article className={`wordpress-content${isProjectPost ? ' projects-content' : ''}`} dangerouslySetInnerHTML={{ __html: pageBodyContent }} />}
          {isProjectPost && projectPhotos.length > 0 && <section className="project-gallery-section"><p className="eyebrow"><span /> PROJECT PHOTOS</p><div className="project-gallery-grid">{projectPhotos.map((url, index) => <figure className="project-gallery-item" key={`${url}-${index}`}><img src={url} alt={`${title} project photo ${index + 1}`} loading="lazy" /></figure>)}</div></section>}
          {slug === 'sustainability' && <section className="inner-calculator sustainability-calculator"><div><p className="eyebrow"><span /> MATERIAL RECOVERY MODEL</p><h2>{pageCopy('sustainability_heading', 'Model an illustrative equivalent')}</h2><p>{pageCopy('sustainability_description', 'Illustrative estimates of recovered materials and avoided emissions from collected electronic waste.')}</p><p className="sustainability-disclaimer">{pageCopy('sustainability_factor_note', 'Illustrative assumptions only. Actual material recovery and emissions outcomes require verified project data.')}</p></div><div className="calculator light-calculator"><div className="sustainability-model-controls"><label>Waste stream<select value={wasteModel} onChange={(event) => setWasteModel(event.target.value as typeof wasteModel)}><option value="mixed">Mixed electronics</option><option value="it">IT equipment</option><option value="mobile">Mobile devices</option></select></label><label>Recovery scenario<select value={recoveryScenario} onChange={(event) => setRecoveryScenario(event.target.value as typeof recoveryScenario)}><option value="conservative">Conservative</option><option value="base">Illustrative</option><option value="optimistic">Optimistic</option></select></label></div><label htmlFor="inner-volume">Electronic waste collected <strong>{wasteKg.toLocaleString()} kg</strong></label><input id="inner-volume" type="range" min="1000" max="100000" step="1000" value={wasteKg} onChange={(event) => setWasteKg(Number(event.target.value))} /><div className="sustainability-results" aria-live="polite"><div><strong>{formatModelEstimate(estimatedMetalsKg, 'kg')}</strong><span>Estimated metals recovered</span></div><div><Leaf size={18} /><strong>{formatModelEstimate(estimatedCo2eKg, 'kg CO2e')}</strong><span>Illustrative CO2e avoided</span></div><div><strong>{formatModelEstimate(estimatedEnergyKwh, 'kWh')}</strong><span>Estimated energy recovered</span></div><div><strong>{formatModelEstimate(estimatedLandfillKg, 'kg')}</strong><span>Estimated landfill diverted</span></div><div><strong>{formatModelEstimate(estimatedWaterLitres, 'L')}</strong><span>Illustrative water savings</span></div></div><p className="sustainability-assumptions">{sustainabilityModelNote}</p></div></section>}
          {slug === 'projects' && <>
            <section className="project-list"><p className="eyebrow"><span /> PROJECTS</p><h2>{pageCopy('projects_section_heading', 'Selected projects')}</h2><p>{projects.length ? pageCopy('projects_section_description', 'Explore project work and case studies published by Van Regen.') : pageCopy('projects_empty_description', 'Verified project details will appear here once they are added in WordPress.')}</p><div className="project-grid">{projects.map((item) => <ProjectCard project={item} key={item.id} />)}</div></section>
            <section className="project-gallery-section">
              <div className="project-gallery-intro">
                <p className="eyebrow"><span /> GALLERY</p>
                <h2>{pageCopy('projects_gallery_heading', 'Project gallery')}</h2>
              </div>
              {hasProjectGalleryPhotos ? <div className="project-gallery-groups">
                {projectGalleryGroups.map(({ project, photos }) => <button
                  className="project-gallery-card"
                  type="button"
                  key={project.id}
                  onClick={() => setActiveGallery({ title: cleanText(project.title.rendered), photos })}
                  aria-label={`Open ${cleanText(project.title.rendered)} photo gallery`}
                >
                  <img src={photos[0]} alt="" loading="lazy" />
                  <span>{cleanText(project.title.rendered)}</span>
                  <ArrowUpRight size={20} aria-hidden="true" />
                </button>)}
                {pageGalleryPhotos.length > 0 && <button
                  className="project-gallery-card"
                  type="button"
                  onClick={() => setActiveGallery({ title: 'More from our work', photos: [...new Set(pageGalleryPhotos)] })}
                  aria-label="Open additional project photos"
                >
                  <img src={pageGalleryPhotos[0]} alt="" loading="lazy" />
                  <span>More from our work</span>
                  <ArrowUpRight size={20} aria-hidden="true" />
                </button>}
              </div> : <p className="project-gallery-empty">{pageCopy('projects_gallery_empty', 'Gallery photos will appear here once they are added in WordPress.')}</p>}
            </section>
          </>}
          {(slug === 'contact' || slug === 'contact-quote') && <section className="contact-section"><div className="contact-intro"><p className="eyebrow"><span /> {slug === 'contact-quote' ? 'REQUEST A QUOTE' : 'GET IN TOUCH'}</p><h2>{contactFormHeading}</h2><p>{contactFormDescription}</p><div className="contact-details"><address><MapPin size={18} /><span>{pageCopy('contact_address', 'Plant Gat No. 182, Baramati Road, Near Palkhi Mahamarg, Patas, Tal. Daund, Dist. Pune, Maharashtra - 412219')}</span></address>{contactEmails.map((email) => <a href={`mailto:${email}`} key={email}><Mail size={18} /><span>{email}</span></a>)}{contactPhones.map((phone) => <a href={`tel:${phone.replace(/[^\d+]/g, '')}`} key={phone}><Phone size={18} /><span>{phone}</span></a>)}</div><div className="contact-note"><ShieldCheck size={18} /><span>{pageCopy('contact_privacy_note', 'Your information is used to respond to this inquiry. Please do not include sensitive personal information.')}</span></div></div><form className="inquiry-form" onSubmit={submitInquiry}>
            {formState === 'sent' ? <div className="form-success" role="status"><Check size={22} /><div><strong>{formSuccessMessage}</strong><p>The team will follow up using the contact details you provided.</p></div></div> : <>
              <div className="form-row"><label>Your name<input required autoComplete="name" value={inquiry.name} onChange={(event) => updateInquiry('name', event.target.value)} /></label><label>Company<input required autoComplete="organization" value={inquiry.company} onChange={(event) => updateInquiry('company', event.target.value)} /></label></div>
              <div className="form-row"><label>Work email<input required type="email" autoComplete="email" value={inquiry.email} onChange={(event) => updateInquiry('email', event.target.value)} /></label><label>Phone<input required type="tel" autoComplete="tel" value={inquiry.phone} onChange={(event) => updateInquiry('phone', event.target.value)} /></label></div>
              {slug === 'contact-quote' && <div className="inquiry-services">
                <p className="inquiry-products-heading">Services, products and quantities</p>
                {inquiry.services.map((selection, serviceIndex) => {
                  const selectedService = serviceItems.find((service) => service.title === selection.service);
                  const selectedServicesElsewhere = new Set(inquiry.services
                    .filter((_, index) => index !== serviceIndex)
                    .map((service) => service.service));
                  const availableServices = serviceItems.filter((service) => (
                    service.title === selection.service || !selectedServicesElsewhere.has(service.title)
                  ));
                  const productChoices = selectedService
                    ? [...(selectedService.products ?? []), 'Other / not listed']
                    : [];
                  const canAddProduct = Boolean(selectedService) && selection.products.length < productChoices.length;

                  return <section className="inquiry-service-group" key={`inquiry-service-${serviceIndex}`}>
                    <div className="inquiry-service-heading">
                      <label htmlFor={`inquiry-service-${serviceIndex}`}>Service
                        <select
                          id={`inquiry-service-${serviceIndex}`}
                          required
                          value={selection.service}
                          onChange={(event) => selectInquiryService(serviceIndex, event.target.value)}
                        >
                          <option value="">Select a service</option>
                          {availableServices.map((service) => <option value={service.title} key={service.slug}>{service.title}</option>)}
                        </select>
                      </label>
                      {serviceIndex > 0 && <button className="inquiry-product-remove" type="button" onClick={() => removeInquiryService(serviceIndex)}>Remove service</button>}
                    </div>
                    <div className="inquiry-product-list">
                      {selection.products.map((productSelection, productIndex) => {
                        const selectedProductsElsewhere = new Set(selection.products
                          .filter((_, index) => index !== productIndex)
                          .map((product) => product.product));
                        const availableProducts = productChoices.filter((product) => (
                          product === productSelection.product || !selectedProductsElsewhere.has(product)
                        ));
                        return <div className="form-row inquiry-product-row" key={`inquiry-product-${serviceIndex}-${productIndex}`}>
                          <label htmlFor={`inquiry-product-${serviceIndex}-${productIndex}`}>Product / asset
                            <select
                              id={`inquiry-product-${serviceIndex}-${productIndex}`}
                              required
                              disabled={!selectedService}
                              value={productSelection.product}
                              onChange={(event) => updateInquiryProduct(serviceIndex, productIndex, 'product', event.target.value)}
                            >
                              <option value="">{selectedService ? 'Select a product or asset' : 'Select a service first'}</option>
                              {availableProducts.map((product) => <option value={product} key={product}>{product === 'Other / not listed' ? 'Other / not listed — describe below' : product}</option>)}
                            </select>
                          </label>
                          <label htmlFor={`inquiry-quantity-${serviceIndex}-${productIndex}`}>Quantity
                            <input
                              id={`inquiry-quantity-${serviceIndex}-${productIndex}`}
                              type="number"
                              min="1"
                              step="1"
                              required
                              disabled={!selectedService}
                              value={productSelection.quantity}
                              onChange={(event) => updateInquiryProduct(serviceIndex, productIndex, 'quantity', event.target.value)}
                            />
                          </label>
                          {productIndex > 0 && <button className="inquiry-product-remove" type="button" onClick={() => removeInquiryProduct(serviceIndex, productIndex)}>Remove product</button>}
                        </div>;
                      })}
                    </div>
                    <button className="inquiry-product-add" type="button" disabled={!canAddProduct} onClick={() => addInquiryProduct(serviceIndex)}>+ Add product to this service</button>
                    <p className="inquiry-products-note">Choose each product once. Select “Other / not listed” and describe it in your message if needed.</p>
                  </section>;
                })}
                <button className="inquiry-product-add" type="button" disabled={!canAddInquiryService} onClick={addInquiryService}>+ Add another service</button>
              </div>}
              <label htmlFor="inquiry-message">How can we help?<textarea id="inquiry-message" required rows={5} value={inquiry.message} onChange={(event) => updateInquiry('message', event.target.value)} /></label>
              <label className="honeypot" aria-hidden="true">Leave this field empty<input tabIndex={-1} autoComplete="off" value={inquiry.website} onChange={(event) => updateInquiry('website', event.target.value)} /></label>
              <label className="consent"><input required type="checkbox" checked={inquiry.consent} onChange={(event) => updateInquiry('consent', event.target.checked)} /><span>I agree to be contacted about this inquiry.</span></label>
              {formError && <p className="form-error" role="alert">{formError}</p>}
              <button className="button button-dark" type="submit" disabled={formState === 'sending'}>{formState === 'sending' ? 'Sending...' : 'Send inquiry'} <ArrowRight size={17} /></button>
            </>}</form></section>}
          {!isServiceDetail && !content && !['services', 'sustainability', 'projects', 'contact', 'contact-quote'].includes(slug) && <section className="page-placeholder"><p>Page content is ready to be managed in the WordPress editor.</p><a className="text-link dark-link" href={pageUrl('contact')}>Discuss a project <ArrowRight size={16} /></a></section>}
        </main>
      )}

      <footer className="site-footer">
        <div className="footer-top">
          <div className="footer-about">
            <a className="brand footer-brand" href={pageUrl('home')}>
              <span className="brand-mark">{api?.logoUrl ? <img className="brand-logo" src={api.logoUrl} alt="" /> : <Recycle size={24} />}</span>
              <span className="brand-name">{siteCopy('brandName', 'VAN REGEN')}<small>{siteCopy('brandTagline', 'RESPONSIBLE RESOURCE RECOVERY')}</small></span>
            </a>
            <p>{siteCopy('footerStatement', 'Resource recovery for a world\nthat keeps moving.').split('\n').map((line, index) => <Fragment key={line + index}>{index > 0 && <br />}{line}</Fragment>)}</p>
          </div>
          <nav className="footer-column footer-explore" aria-label="Footer navigation">
            <h2>Explore</h2>
            {navigationItems.map((item) => <a key={item.id} href={item.url} target={item.target || undefined}>{cleanText(item.title)} <ArrowUpRight size={13} /></a>)}
          </nav>
          <div className="footer-column footer-services">
            <h2>Services</h2>
            {(navigationItems.find((item) => cleanText(item.title).toLowerCase() === 'services')?.children ?? [])
              .filter((item) => cleanText(item.title).length > 0)
              .map((item) => <a key={item.id} href={item.url} target={item.target || undefined}>{cleanText(item.title)} <ArrowUpRight size={13} /></a>)}
          </div>
          <div className="footer-cta-card">
            <span>READY TO PLAN YOUR NEXT STEP?</span>
            <h2>Let’s move materials forward.</h2>
            <p>Share your requirements and our team can help define the right scope.</p>
            <a className="footer-contact" href={pageUrl('contact')}>{siteCopy('footerCta', 'Start a conversation')} <ArrowUpRight size={17} /></a>
          </div>
        </div>
        <div className="footer-bottom">
          <span>© {new Date().getFullYear()} {siteCopy('companyName', 'VAN REGEN PRIVATE LIMITED')}</span>
          <span className="draft-note">{siteCopy('footerNote', 'Capabilities and impact statements require owner verification.')}</span>
          <p className="footer-credit">{siteCopy('footerCredit', 'Website By Money Way Digital Marketing Solutions')}</p>
        </div>
      </footer>
      {activeGallery && <div className="gallery-modal" role="dialog" aria-modal="true" aria-labelledby="gallery-modal-title" onClick={() => setActiveGallery(null)}>
        <div className="gallery-modal-panel" onClick={(event) => event.stopPropagation()}>
          <div className="gallery-modal-header">
            <h2 id="gallery-modal-title">{activeGallery.title}</h2>
            <button type="button" onClick={() => setActiveGallery(null)} aria-label="Close gallery"><X size={24} /></button>
          </div>
          <div className="gallery-modal-grid">{activeGallery.photos.map((url, index) => <figure key={`${url}-${index}`}><img src={url} alt={`${activeGallery.title} photo ${index + 1}`} /></figure>)}</div>
        </div>
      </div>}
    </div>
  );
}

export default App;
