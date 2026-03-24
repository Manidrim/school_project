interface IAdminCardProps {
  title: string;
  description: string;
  buttonText: string;
  href?: string;
  onClick?: () => void;
  colorScheme: 'blue' | 'green' | 'purple' | 'indigo';
}

const COLOR_CLASSES = {
  blue: {
    bg: 'bg-blue-50',
    border: 'border-blue-200',
    titleText: 'text-blue-900',
    descText: 'text-blue-700',
    button: 'bg-blue-500 hover:bg-blue-600'
  },
  green: {
    bg: 'bg-green-50',
    border: 'border-green-200',
    titleText: 'text-green-900',
    descText: 'text-green-700',
    button: 'bg-green-500 hover:bg-green-600'
  },
  purple: {
    bg: 'bg-purple-50',
    border: 'border-purple-200',
    titleText: 'text-purple-900',
    descText: 'text-purple-700',
    button: 'bg-purple-500 hover:bg-purple-600'
  },
  indigo: {
    bg: 'bg-indigo-50',
    border: 'border-indigo-200',
    titleText: 'text-indigo-900',
    descText: 'text-indigo-700',
    button: 'bg-indigo-500 hover:bg-indigo-600'
  }
};

const AdminCard = ({ 
  title, 
  description, 
  buttonText, 
  href, 
  onClick, 
  colorScheme 
}: IAdminCardProps): JSX.Element => {
  const colors = COLOR_CLASSES[colorScheme];

  return (
    <div className={`${colors.bg} border ${colors.border} rounded-lg p-4`}>
      <h3 className={`text-lg font-medium ${colors.titleText} mb-2`}>{title}</h3>
      <p className={`${colors.descText} text-sm mb-3`}>{description}</p>
      {href !== undefined ? (
        <a
          href={href}
          className={`${colors.button} text-white font-medium py-2 px-4 rounded text-sm`}
        >
          {buttonText}
        </a>
      ) : (
        <button
          onClick={onClick}
          className={`${colors.button} text-white font-medium py-2 px-4 rounded text-sm`}
        >
          {buttonText}
        </button>
      )}
    </div>
  );
};

export default AdminCard;
export type { IAdminCardProps };