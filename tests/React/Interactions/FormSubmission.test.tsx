import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { router } from '@inertiajs/react';

describe('Form Submission Interactions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('validates required fields before submission', async () => {
    // Mock form component
    const MockForm = () => {
      const [errors, setErrors] = React.useState<Record<string, string>>({});

      const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        const newErrors: Record<string, string> = {};

        if (!formData.get('item_name')) {
          newErrors.item_name = 'Item name is required';
        }

        if (Object.keys(newErrors).length > 0) {
          setErrors(newErrors);
          return;
        }

        router.post('/purchase-requests', Object.fromEntries(formData));
      };

      return (
        <form onSubmit={handleSubmit}>
          <input name="item_name" placeholder="Item name" />
          {errors.item_name && <span>{errors.item_name}</span>}
          <button type="submit">Submit</button>
        </form>
      );
    };

    render(<MockForm />);

    const submitButton = screen.getByText('Submit');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText('Item name is required')).toBeInTheDocument();
    });

    expect(router.post).not.toHaveBeenCalled();
  });

  it('submits form with valid data', async () => {
    const MockForm = () => {
      const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        router.post('/purchase-requests', Object.fromEntries(formData));
      };

      return (
        <form onSubmit={handleSubmit}>
          <input name="item_name" placeholder="Item name" />
          <input name="quantity" type="number" placeholder="Quantity" />
          <button type="submit">Submit</button>
        </form>
      );
    };

    render(<MockForm />);

    const itemNameInput = screen.getByPlaceholderText('Item name');
    const quantityInput = screen.getByPlaceholderText('Quantity');
    const submitButton = screen.getByText('Submit');

    fireEvent.change(itemNameInput, { target: { value: 'Office Chair' } });
    fireEvent.change(quantityInput, { target: { value: '2' } });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(router.post).toHaveBeenCalledWith('/purchase-requests', {
        item_name: 'Office Chair',
        quantity: '2',
      });
    });
  });

  it('disables submit button during submission', async () => {
    const MockForm = () => {
      const [isSubmitting, setIsSubmitting] = React.useState(false);

      const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        
        // Simulate API call
        await new Promise((resolve) => setTimeout(resolve, 100));
        
        router.post('/purchase-requests', {});
        setIsSubmitting(false);
      };

      return (
        <form onSubmit={handleSubmit}>
          <button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Submitting...' : 'Submit'}
          </button>
        </form>
      );
    };

    render(<MockForm />);

    const submitButton = screen.getByText('Submit');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText('Submitting...')).toBeInTheDocument();
      expect(screen.getByText('Submitting...')).toBeDisabled();
    });
  });

  it('displays validation errors from server', async () => {
    const MockForm = () => {
      const [errors, setErrors] = React.useState<Record<string, string>>({});

      const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Simulate server validation errors
        setErrors({
          item_name: 'The item name field is required.',
          quantity: 'The quantity must be at least 1.',
        });
      };

      return (
        <form onSubmit={handleSubmit}>
          <div>
            <input name="item_name" placeholder="Item name" />
            {errors.item_name && (
              <span className="error">{errors.item_name}</span>
            )}
          </div>
          <div>
            <input name="quantity" type="number" placeholder="Quantity" />
            {errors.quantity && (
              <span className="error">{errors.quantity}</span>
            )}
          </div>
          <button type="submit">Submit</button>
        </form>
      );
    };

    render(<MockForm />);

    const submitButton = screen.getByText('Submit');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText('The item name field is required.')).toBeInTheDocument();
      expect(screen.getByText('The quantity must be at least 1.')).toBeInTheDocument();
    });
  });

  it('clears validation errors when input changes', async () => {
    const MockForm = () => {
      const [errors, setErrors] = React.useState<Record<string, string>>({
        item_name: 'Item name is required',
      });

      const handleChange = () => {
        setErrors({});
      };

      return (
        <form>
          <input
            name="item_name"
            placeholder="Item name"
            onChange={handleChange}
          />
          {errors.item_name && <span>{errors.item_name}</span>}
        </form>
      );
    };

    render(<MockForm />);

    expect(screen.getByText('Item name is required')).toBeInTheDocument();

    const input = screen.getByPlaceholderText('Item name');
    fireEvent.change(input, { target: { value: 'Office Chair' } });

    await waitFor(() => {
      expect(screen.queryByText('Item name is required')).not.toBeInTheDocument();
    });
  });

  it('prevents double submission', async () => {
    const MockForm = () => {
      const [isSubmitting, setIsSubmitting] = React.useState(false);

      const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        if (isSubmitting) return;
        
        setIsSubmitting(true);
        await new Promise((resolve) => setTimeout(resolve, 100));
        router.post('/purchase-requests', {});
        setIsSubmitting(false);
      };

      return (
        <form onSubmit={handleSubmit}>
          <button type="submit" disabled={isSubmitting}>
            Submit
          </button>
        </form>
      );
    };

    render(<MockForm />);

    const submitButton = screen.getByText('Submit');
    
    // Try to submit multiple times
    fireEvent.click(submitButton);
    fireEvent.click(submitButton);
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(router.post).toHaveBeenCalledTimes(1);
    });
  });

  it('handles file upload', async () => {
    const MockForm = () => {
      const [file, setFile] = React.useState<File | null>(null);

      const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
          setFile(e.target.files[0]);
        }
      };

      const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData();
        if (file) {
          formData.append('image', file);
        }
        router.post('/purchase-requests', formData);
      };

      return (
        <form onSubmit={handleSubmit}>
          <input type="file" onChange={handleFileChange} />
          {file && <span>Selected: {file.name}</span>}
          <button type="submit">Submit</button>
        </form>
      );
    };

    render(<MockForm />);

    const fileInput = screen.getByRole('button', { name: /submit/i }).previousElementSibling as HTMLInputElement;
    const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });

    fireEvent.change(fileInput, { target: { files: [file] } });

    await waitFor(() => {
      expect(screen.getByText('Selected: test.jpg')).toBeInTheDocument();
    });

    const submitButton = screen.getByText('Submit');
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(router.post).toHaveBeenCalled();
    });
  });
});
